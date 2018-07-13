<?php

declare(strict_types=1);

namespace Model;

use Assert\Assert;
use DateTimeImmutable;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ServerException;
use Model\DTO\Payment as DTO;
use Model\Payment\BankException;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\Group\PaymentDefaults;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFoundException;
use Model\Payment\MissingVariableSymbolException;
use Model\Payment\Payment;
use Model\Payment\Payment\State;
use Model\Payment\PaymentNotFoundException;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;
use Model\Services\Language;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use function array_filter;
use function array_key_exists;
use function array_map;
use function count;
use function date;
use function explode;
use function in_array;
use function is_array;
use function mb_substr;
use function reset;
use function strcmp;
use function strlen;
use function trim;
use function usort;

class PaymentService
{
    /** @var string */
    private $tempDir;

    /** @var Skautis */
    private $skautis;

    /** @var IGroupRepository */
    private $groups;

    /** @var IPaymentRepository */
    private $payments;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var ClientInterface */
    private $http;

    public function __construct(
        string $tempDir,
        Skautis $skautis,
        IGroupRepository $groups,
        IPaymentRepository $payments,
        IBankAccountRepository $bankAccounts,
        ClientInterface $http
    ) {
        $this->tempDir      = $tempDir;
        $this->skautis      = $skautis;
        $this->groups       = $groups;
        $this->payments     = $payments;
        $this->bankAccounts = $bankAccounts;
        $this->http         = $http;
    }

    public function findPayment(int $id) : ?DTO\Payment
    {
        try {
            return DTO\PaymentFactory::create($this->payments->find($id));
        } catch (PaymentNotFoundException $e) {
            return null;
        }
    }

    /**
     * @return DTO\Payment[]
     */
    public function findByGroup(int $groupId) : array
    {
        $payments = $this->payments->findByGroup($groupId);

        return array_map(
            function (Payment $payment) {
                return DTO\PaymentFactory::create($payment);
            },
            $payments
        );
    }

    public function createPayment(int $groupId, string $name, ?string $email, float $amount, DateTimeImmutable $dueDate, ?int $personId, ?VariableSymbol $vs, ?int $ks, string $note) : void
    {
        $group = $this->groups->find($groupId);

        $payment = new Payment($group, $name, $email, $amount, $dueDate, $vs, $ks, $personId, $note);

        $this->payments->save($payment);
    }

    public function update(
        int $id,
        string $name,
        ?string $email,
        float $amount,
        DateTimeImmutable $dueDate,
        ?VariableSymbol $variableSymbol,
        ?int $constantSymbol,
        string $note
    ) : void {
        $payment = $this->payments->find($id);

        $payment->update($name, $email, $amount, $dueDate, $variableSymbol, $constantSymbol, $note);

        $this->payments->save($payment);
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
        $payment->complete(new DateTimeImmutable());

        $this->payments->save($payment);
    }

    /**
     * GROUP
     */

    /**
     * @param int[] $unitIds
     * @return DTO\Group[]
     */
    public function getGroups(array $unitIds, bool $onlyOpen) : array
    {
        $groups = $this->groups->findByUnits($unitIds, $onlyOpen);

        return array_map(
            function (Group $group) {
                return DTO\GroupFactory::create($group);
            },
            $groups
        );
    }


    /**
     * @param int[] $ids
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
        ?int $smtpId,
        ?int $bankAccountId
    ) : int {
        $now         = new DateTimeImmutable();
        $bankAccount = $bankAccountId !== null ? $this->bankAccounts->find($bankAccountId) : null;

        $group = new Group($unitId, $skautisEntity, $label, $paymentDefaults, $now, $emails, $smtpId, $bankAccount);

        $this->groups->save($group);
        return $group->getId();
    }

    /**
     * @param array<string,EmailTemplate> $emails
     */
    public function updateGroup(
        int $id,
        string $name,
        PaymentDefaults $paymentDefaults,
        array $emails,
        ?int $smtpId,
        ?int $bankAccountId
    ) : void {
        $group       = $this->groups->find($id);
        $bankAccount = $bankAccountId !== null ? $this->bankAccounts->find($bankAccountId) : null;

        $group->update($name, $paymentDefaults, $smtpId, $bankAccount);

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
        } catch (GroupNotFoundException $e) {
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
     * seznam osob z dané jednotky
     *
     * @param  int $groupId - skupina plateb, podle které se filtrují osoby, které již mají platbu zadanou
     * @return DTO\Person[]
     */
    public function getPersons(int $unitId, int $groupId) : array
    {
        $persons = $this->skautis->org->PersonAll(['ID_Unit' => $unitId, 'OnlyDirectMember' => true]);

        if (! is_array($persons) || empty($persons)) {
            return [];
        }

        $personsWithPayment = $this->getPersonsWithActivePayment($groupId);

        $result = [];
        foreach ($persons as $person) {
            if (in_array($person->ID, $personsWithPayment)) {
                continue;
            }

            $result[] = new DTO\Person($person->ID, $person->DisplayName, $this->getPersonEmails($person->ID));
        }

        usort(
            $result,
            function (DTO\Person $one, DTO\Person $two) {
                return Language::compare($one->getName(), $two->getName());
            }
        );

        return $result;
    }

    /**
     * vrací seznam emailů osoby
     *
     * @return string[]
     */
    public function getPersonEmails(int $personId) : array
    {
        $result = [];
        try {
            $emails = $this->skautis->org->PersonContactAll(['ID_Person' => $personId]);
            if (is_array($emails)) {
                usort(
                    $emails,
                    function ($a, $b) {
                        return $a->IsMain === $b->IsMain ? 0 : ($a->IsMain > $b->IsMain) ? -1 : 1;
                    }
                );
                foreach ($emails as $c) {
                    if (mb_substr($c->ID_ContactType, 0, 5) !== 'email') {
                        continue;
                    }

                    $result[$c->Value] = $c->Value . ' (' . $c->ContactType . ')';
                }
            }
        } catch (PermissionException $exc) {//odchycení bývalých členů, ke kterým už nemáme oprávnění
        }
        return $result;
    }

    /**
     * REGISTRATION
     */

    /**
     * Returns newest registration without created group
     */
    public function getNewestRegistration() : array
    {
        $unitId = $this->skautis->getUser()->getUnitId();

        $data = $this->skautis->org->UnitRegistrationAll(['ID_Unit' => $unitId, '']);

        if ($data !== new \stdClass()) { // Skautis returns empty object when no registration is found
            $registration = $data[0];

            $groups = $this->groups->findBySkautisEntity(
                new Group\SkautisEntity($registration->ID, Type::get(Type::REGISTRATION))
            );

            if (empty($groups)) {
                return (array) $registration;
            }
        }

        return [];
    }

    /**
     * seznam osob z registrace
     *
     * @param int[] $units
     * @param int   $groupId ID platebni skupiny, podle ktere se filtruji osoby bez platby
     */
    public function getPersonsFromRegistrationWithoutPayment(array $units, int $groupId) : array
    {
        $result = [];

        $group = $this->getGroup($groupId);

        if ($group === null || ! in_array($group->getUnitId(), $units, true)) {
            throw new \InvalidArgumentException('Nebyla nalezena platební skupina');
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
                $result[$p->ID_Person]['emails'] = $this->getPersonEmails($p->ID_Person);
            }
        }
        return $result;
    }

    public function getPersonFromRegistration(?int $registrationId, bool $includeChild = true)
    {
        $persons = $this->skautis->org->PersonRegistrationAll([
            'ID_UnitRegistration' => $registrationId,
            'IncludeChild' => $includeChild,
        ]);

        usort($persons, function ($one, $two) {
            return Language::compare($one->Person, $two->Person);
        });

        return $persons;
    }

    /**
     * JOURNAL
     */

    /**
     * @return array - format array("add" => [], "remove" => [])
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
            $isRegustredWithJournal = $regCategories[$p->ID_RegistrationCategory];
            $hasPersonJournal       = array_key_exists($p->ID_Person, $personIdsWithJournal);
            if ($hasPersonJournal && ! $isRegustredWithJournal) {
                $changes['remove'][] = $p->Person;
            } elseif (! $hasPersonJournal && $isRegustredWithJournal) {
                $changes['add'][] = $p->Person;
            }
        }
        return $changes;
    }

    /**
     * CAMP
     */
    public function getCamp($campId)
    {
        return $this->skautis->event->{'EventCampDetail'}(['ID' => $campId]);
    }

    /**
     * vrací seznam id táborů se založenou aktivní skupinou
     */
    public function getCampIds()
    {
        $groups = $this->groups->findBySkautisEntityType(Type::get(Type::CAMP));

        return array_map(
            function (Group $group) {
                return $group->getObject()->getId();
            },
            $groups
        );
    }

    /* Repayments */

    public function getFioRepaymentString($repayments, $accountFrom, $date = null) : string
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }
        $accountFromArr = explode('/', $accountFrom, 2);

        $ret = '<?xml version="1.0" encoding="UTF-8"?><Import xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="http://www.fio.cz/schema/importIB.xsd"> <Orders>';
        foreach ($repayments as $r) {
            $accountArr = explode('/', $r['account'], 2);
            $ret       .= '<DomesticTransaction>';
            $ret       .= '<accountFrom>' . $accountFromArr[0] . '</accountFrom>';
            $ret       .= '<currency>CZK</currency>';
            $ret       .= '<amount>' . $r['amount'] . '</amount>';
            $ret       .= '<accountTo>' . $accountArr[0] . '</accountTo>';
            $ret       .= '<bankCode>' . $accountArr[1] . '</bankCode>';
            $ret       .= '<date>' . $date . '</date>';
            $ret       .= '<messageForRecipient>' . $r['name'] . '</messageForRecipient>';
            $ret       .= '<comment></comment>';
            $ret       .= '<paymentType>431001</paymentType>';
            $ret       .= '</DomesticTransaction>';
        }
        $ret .= '</Orders></Import>';
        return $ret;
    }

    /**
     * @throws BankException
     */
    public function sendFioPaymentRequest(string $stringToRequest, string $token) : void
    {
        try {
            $this->http->request(
                'POST',
                'https://www.fio.cz/ib_api/rest/import/',
                [
                'multipart' => [
                    ['name' => 'token', 'contents' => $token],
                    ['name' => 'type', 'contents' => 'xml'],
                    ['name' => 'file', 'contents' => $stringToRequest, 'filename' => 'request.xml'],
                    ['name' => 'lng', 'contents' => 'cs'],
                ],
                'timeout' => 60,
                ]
            );
        } catch (ServerException $e) {
            throw new BankException($this->getErrorMessage($e), 0, $e);
        }
    }

    /**
     * @throws MissingVariableSymbolException
     */
    public function generateVs(int $gid) : int
    {
        $nextVariableSymbol = $this->getNextVS($gid);

        if ($nextVariableSymbol === null) {
            throw new MissingVariableSymbolException();
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

    private function getErrorMessage(ServerException $exception) : string
    {
        if ($exception->getResponse() === null) {
            return $exception->getMessage();
        }

        $body = $exception->getResponse()->getBody()->getContents();

        if (strlen(trim($body)) === 0) {
            return $exception->getMessage();
        }

        $result = new \SimpleXMLElement($body);

        return (string)($result->ordersDetails->detail->messages->message ?? '');
    }
}
