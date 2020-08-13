<?php

declare(strict_types=1);

namespace Model;

use Assert\Assert;
use DateTimeImmutable;
use InvalidArgumentException;
use Model\Common\Repositories\IUserRepository;
use Model\DTO\Payment as DTO;
use Model\Google\OAuthId;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFound;
use Model\Payment\MissingVariableSymbol;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFound;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IMemberEmailRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\Services\IBankAccountAccessChecker;
use Model\Payment\Services\IOAuthAccessChecker;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;
use Model\Services\Language;
use Skautis\Skautis;
use stdClass;
use function array_filter;
use function array_intersect;
use function array_key_exists;
use function array_map;
use function count;
use function in_array;
use function is_array;
use function reset;
use function strcmp;
use function usort;

class PaymentService
{
    /** @var Skautis */
    private $skautis;

    /** @var IGroupRepository */
    private $groups;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IBankAccountAccessChecker */
    private $bankAccountAccessChecker;

    /** @var IMemberEmailRepository */
    private $emails;

    /** @var IOAuthAccessChecker */
    private $oAuthAccessChecker;

    /** @var IUserRepository */
    private $users;

    public function __construct(
        Skautis $skautis,
        IGroupRepository $groups,
        IPaymentRepository $payments,
        IBankAccountRepository $bankAccounts,
        IBankAccountAccessChecker $bankAccountAccessChecker,
        IMemberEmailRepository $emails,
        IOAuthAccessChecker $oAuthAccessChecker,
        IUserRepository $users
    ) {
        $this->skautis                  = $skautis;
        $this->groups                   = $groups;
        $this->payments                 = $payments;
        $this->bankAccounts             = $bankAccounts;
        $this->bankAccountAccessChecker = $bankAccountAccessChecker;
        $this->emails                   = $emails;
        $this->oAuthAccessChecker       = $oAuthAccessChecker;
        $this->users                    = $users;
    }

    public function findPayment(int $id) : ?DTO\Payment
    {
        try {
            return DTO\PaymentFactory::create($this->payments->find($id));
        } catch (PaymentNotFound $e) {
            return null;
        }
    }

    public function cancelPayment(int $pid) : void
    {
        $payment = $this->payments->find($pid);
        $payment->cancel(new DateTimeImmutable());

        $this->payments->save($payment);
    }

    public function completePayment(int $id) : void
    {
        $payment = $this->payments->find($id);
        $payment->completeManually(new DateTimeImmutable(), $this->users->getCurrentUser()->getName());

        $this->payments->save($payment);
    }

    /**
     * GROUP
     */

    /**
     * @param int[] $ids
     *
     * @return DTO\Group[]
     */
    public function findGroupsByIds(array $ids) : array
    {
        Assert::thatAll($ids)->integer();
        $groups = $this->groups->findByIds($ids);

        return array_map(
            function (Group $g) {
                return DTO\GroupFactory::create($g);
            },
            $groups
        );
    }

    /**
     * @param int[] $groupIds
     *
     * @return Summary[][]
     */
    public function getGroupSummaries(array $groupIds) : array
    {
        return $this->payments->summarizeByGroup($groupIds);
    }

    /**
     * @param EmailTemplate[] $emails
     */
    public function createGroup(
        int $unitId,
        ?SkautisEntity $skautisEntity,
        string $label,
        PaymentDefaults $paymentDefaults,
        array $emails,
        ?OAuthId $oAuthId,
        ?int $bankAccountId
    ) : int {
        $now         = new DateTimeImmutable();
        $bankAccount = $bankAccountId !== null ? $this->bankAccounts->find($bankAccountId) : null;

        $group = new Group(
            [$unitId],
            $skautisEntity,
            $label,
            $paymentDefaults,
            $now,
            $emails,
            $oAuthId,
            $bankAccount,
            $this->bankAccountAccessChecker,
            $this->oAuthAccessChecker,
        );

        $this->groups->save($group);

        return $group->getId();
    }

    /**
     * @param EmailTemplate[] $emails
     */
    public function updateGroup(
        int $id,
        string $name,
        PaymentDefaults $paymentDefaults,
        array $emails,
        ?OAuthId $oAuthId,
        ?int $bankAccountId
    ) : void {
        $group       = $this->groups->find($id);
        $bankAccount = $bankAccountId !== null ? $this->bankAccounts->find($bankAccountId) : null;

        $group->update($name, $paymentDefaults, $oAuthId, $bankAccount, $this->bankAccountAccessChecker, $this->oAuthAccessChecker);

        foreach (EmailType::getAvailableValues() as $typeKey) {
            $type = EmailType::get($typeKey);

            if (isset($emails[$typeKey])) {
                $group->updateEmail($type, $emails[$typeKey]);
                continue;
            }

            $group->disableEmail($type);
        }

        $this->groups->save($group);
    }

    public function getGroup(int $id) : ?DTO\Group
    {
        try {
            $group = $this->groups->find($id);

            return DTO\GroupFactory::create($group);
        } catch (GroupNotFound $e) {
        }

        return null;
    }

    public function openGroup(int $id, string $note) : void
    {
        $group = $this->groups->find($id);
        $group->open($note);
        $this->groups->save($group);
    }

    public function closeGroup(int $id, string $note) : void
    {
        $group = $this->groups->find($id);
        $group->close($note);
        $this->groups->save($group);
    }

    public function getMaxVariableSymbol(int $groupId) : ?VariableSymbol
    {
        return $this->payments->getMaxVariableSymbol($groupId);
    }

    /**
     * vrací nejvyšší hodnotu VS uvedenou ve skupině pro nezrušené platby
     */
    public function getNextVS(int $groupId) : ?VariableSymbol
    {
        $maxVs = $this->payments->getMaxVariableSymbol($groupId);

        if ($maxVs !== null) {
            return $maxVs->increment();
        }

        $group = $this->groups->find($groupId);

        return $group->getNextVariableSymbol();
    }

    /**
     * REGISTRATION
     */

    /**
     * seznam osob z registrace
     *
     * @param int[] $units
     * @param int   $groupId ID platebni skupiny, podle ktere se filtruji osoby bez platby
     *
     * @return mixed[]
     */
    public function getPersonsFromRegistrationWithoutPayment(array $units, int $groupId) : array
    {
        $result = [];

        $group = $this->getGroup($groupId);

        if ($group === null || ! array_intersect($group->getUnitIds(), $units)) {
            throw new InvalidArgumentException('Nebyla nalezena platební skupina');
        }
        $persons = $this->getPersonFromRegistration($group->getSkautisId(), true);

        if (is_array($persons)) {
            usort(
                $persons,
                function ($a, $b) {
                    return strcmp($a->Person, $b->Person);
                }
            );

            $personsWithPayment = $this->getPersonsWithActivePayment($groupId);
            $persons            = array_filter(
                $persons,
                function ($v) use ($personsWithPayment) {
                    return ! in_array($v->ID_Person, $personsWithPayment);
                }
            );

            foreach ($persons as $p) {
                $result[$p->ID_Person]           = (array) $p;
                $result[$p->ID_Person]['emails'] = $this->emails->findByMember($p->ID_Person);
            }
        }

        return $result;
    }

    /**
     * @return stdClass[]
     */
    public function getPersonFromRegistration(?int $registrationId, bool $includeChild = true) : array
    {
        $persons = $this->skautis->org->PersonRegistrationAll([
            'ID_UnitRegistration' => $registrationId,
            'IncludeChild' => $includeChild,
        ]);

        if (! is_array($persons)) {
            return [];
        }

        usort($persons, function ($one, $two) {
            return Language::compare($one->Person, $two->Person);
        });

        return $persons;
    }

    /**
     * @return mixed[] format array("add" => [], "remove" => [])
     */
    public function getJournalChangesAfterRegistration(int $unitId, int $year) : array
    {
        $changes = ['add' => [], 'remove' => []];

        $registrations = $this->skautis->org->UnitRegistrationAll(['ID_Unit' => $unitId, 'Year' => $year]);

        if (! is_array($registrations) || count($registrations) < 1) {
            return $changes;
        }

        $registrationId = reset($registrations)->ID;
        $registration   = $this->getPersonFromRegistration($registrationId, false);

        $regCategories = [];
        foreach ($this->skautis->org->RegistrationCategoryAll(['ID_UnitRegistration' => $registrationId]) as $rc) {
            $regCategories[$rc->ID] = $rc->IsJournal;
        }
        $unitJournals = $this->skautis->Journal->PersonJournalAllUnit(['ID_Unit' => $unitId, 'ShowHistory' => false, 'IncludeChild' => true]);

        //seznam osob s casopisem
        $personIdsWithJournal = [];
        foreach ($unitJournals as $journal) {
            $personIdsWithJournal[$journal->ID_Person] = true;
        }

        foreach ($registration as $p) {
            $isRegisteredWithJournal = $regCategories[$p->ID_RegistrationCategory];
            $hasPersonJournal        = array_key_exists($p->ID_Person, $personIdsWithJournal);
            if ($hasPersonJournal && ! $isRegisteredWithJournal) {
                $changes['remove'][] = $p->Person;
            } elseif (! $hasPersonJournal && $isRegisteredWithJournal) {
                $changes['add'][] = $p->Person;
            }
        }

        return $changes;
    }

    public function getRegistrationYear(int $registrationId) : ?int
    {
        $registration = $this->skautis->org->UnitRegistrationDetail(['ID' => $registrationId]);

        return $registration->Year ?? null;
    }

    /**
     * vrací seznam id táborů se založenou aktivní skupinou
     *
     * @return mixed[]
     */
    public function getCampIds() : array
    {
        $groups = $this->groups->findBySkautisEntityType(Type::get(Type::CAMP));

        return array_map(
            function (Group $group) {
                return $group->getObject()->getId();
            },
            $groups
        );
    }

    /**
     * @throws MissingVariableSymbol
     */
    public function generateVs(int $gid) : int
    {
        $nextVariableSymbol = $this->getNextVS($gid);

        if ($nextVariableSymbol === null) {
            throw new MissingVariableSymbol();
        }

        $payments = $this->payments->findByGroup($gid);

        $payments = array_filter(
            $payments,
            function (Payment $p) {
                return $p->getVariableSymbol() === null && $p->getState()->equalsValue(State::PREPARING);
            }
        );

        foreach ($payments as $payment) {
            $payment->updateVariableSymbol($nextVariableSymbol);
            $nextVariableSymbol = $nextVariableSymbol->increment();
        }

        $this->payments->saveMany($payments);

        return count($payments);
    }

    /**
     * @return int[]
     */
    public function getPersonsWithActivePayment(int $groupId) : array
    {
        $payments = $this->payments->findByGroup($groupId);

        $payments = array_filter(
            $payments,
            function (Payment $payment) {
                return $payment->getPersonId() !== null && ! $payment->getState()->equalsValue(State::CANCELED);
            }
        );

        return array_map(
            function (Payment $p) {
                return $p->getPersonId();
            },
            $payments
        );
    }
}
