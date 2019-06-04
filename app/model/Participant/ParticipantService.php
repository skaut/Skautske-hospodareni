<?php

declare(strict_types=1);

namespace Model;

use Cake\Chronos\Date;
use InvalidArgumentException;
use Model\DTO\Participant\Participant as ParticipantDTO;
use Model\DTO\Payment\ParticipantFactory as ParticipantDTOFactory;
use Model\Event\SkautisEventId;
use Model\Participant\Participant;
use Model\Participant\Payment;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\PaymentNotFound;
use Model\Participant\PragueParticipants;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Services\Language;
use Model\Skautis\Factory\ParticipantFactory;
use Model\Utils\MoneyFactory;
use Nette\Utils\Strings;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use stdClass;
use function array_diff_key;
use function array_key_exists;
use function array_map;
use function array_reduce;
use function assert;
use function is_array;
use function preg_match;
use function sprintf;
use function stripos;
use function usort;

class ParticipantService extends MutableBaseService
{
    private const PRAGUE_SUPPORTABLE_AGE       = 18;
    private const PRAGUE_SUPPORTABLE_UPPER_AGE = 26;
    private const PRAGUE_UNIT_PREFIX           = 11;

    /** @var IPaymentRepository */
    private $repository;

    public function __construct(string $name, Skautis $skautIS, IPaymentRepository $repository)
    {
        parent::__construct($name, $skautIS);
        $this->repository = $repository;
    }

    /**
     * název pod kterým je uložena čáska ve skautISu
     */
    public const PAYMENT = 'Note';

    public function get(int $participantId, int $actionId) : ParticipantDTO
    {
        $data = $this->skautis->event->{'Participant' . $this->typeName . 'Detail'}(['ID' => $participantId]);

        return ParticipantDTOFactory::create(
            ParticipantFactory::create($data, $this->getPayment($participantId, new Event($actionId, EventType::get($this->type))))
        );
    }

    /**
     * vrací seznam účastníků
     * používá lokální úložiště
     *
     * @return ParticipantDTO[]
     */
    public function getAll(int $ID_Event) : array
    {
        $eventId         = new SkautisEventId($ID_Event);
        $participantsSis = (array) $this->skautis->event->{'Participant' . $this->typeName . 'All'}(['ID_Event' . $this->typeName => $eventId->toInt()]);

        $event = new Payment\Event($ID_Event, Payment\EventType::get($this->type));

        if (! is_array($participantsSis)) {
            return []; // API returns empty object when there are no results
        }

        $participantPayments = $this->repository->findByEvent($event);
        $participants        = [];
        foreach ($participantsSis as $p) {
            assert($p instanceof stdClass);

            if (array_key_exists($p->ID, $participantPayments)) {
                $payment =  $participantPayments[$p->ID];
            } elseif (isset($p->{self::PAYMENT})) {
                //TBD: pouze dočasně - po provedení migrace lze odebrat! - 2019/06/04
                $payment =  new Payment(
                    $p->ID,
                    $event,
                    MoneyFactory::fromFloat((float) $p->{self::PAYMENT}),
                    MoneyFactory::zero(),
                    'N'
                );
            } else {
                $payment =  PaymentFactory::createDefault($p->ID, $event);
            }
            $participants[$p->ID] = ParticipantFactory::create($p, $payment);
        }

        foreach (array_diff_key($participantPayments, $participants) as $paymentToRemove) {
            $this->repository->remove($paymentToRemove); //delete zaznam, protoze neexistuje k nemu ucastnik
        }

        usort(
            $participants,
            function (Participant $one, Participant $two) : int {
                return Language::compare($one->getDisplayName(), $two->getDisplayName());
            }
        );

        return array_map([ParticipantDTOFactory::class, 'create'], $participants);
    }

    /**
     * přidat účastníka k akci
     *
     * @throws WsdlException
     */
    public function add(int $ID, int $participantId) : bool
    {
        try {
            return (bool) $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}([
                'ID_Event' . $this->typeName => $ID,
                'ID_Person' => $participantId,
            ]);
        } catch (WsdlException $ex) {
            if (! preg_match('/Chyba validace \(Participant_PersonIsAllreadyParticipant(General)?\)/', $ex->getMessage())) {
                throw $ex;
            }
        }

        return false;
    }

    /**
     * vytvoří nového účastníka
     *
     * @param string[] $person
     */
    public function addNew(int $ID, array $person) : void
    {
        $newParticipantArr = $this->skautis->event->{'Participant' . $this->typeName . 'Insert'}([
            'ID_Event' . $this->typeName => $ID,
            'Person' => [
                'FirstName' => $person['firstName'],
                'LastName' => $person['lastName'],
                'NickName' => $person['nick'],
                'Note' => '',
            ],
        ]);
        $this->personUpdate($newParticipantArr->ID_Person, $person);
    }

    /**
     * upravuje údaje zadané osoby
     *
     * @param mixed[] $data
     */
    public function personUpdate(int $pid, array $data) : void
    {
        $this->skautis->org->PersonUpdateBasic([
            'ID' => $pid,
            'FirstName' => $data['firstName'] ?? null,
            'LastName' => $data['lastName'] ?? null,
            'IdentificationCode' => null,
            'Birthday' => $data['Birthday'] ?? null,
            'Street' => $data['street'] ?? null,
            'City' => $data['city'] ?? null,
            'Postcode' => $data['postcode'] ?? null,
        ]);
    }

    /**
     * @param mixed[] $arr
     */
    public function update(int $participantId, int $actionId, array $arr) : void
    {
        if (array_key_exists('days', $arr)) {
            $sisData = [
                'ID' => $participantId,
                'Real' => true,
                'Days' => $arr['days'],
            ];
            $this->skautis->event->{'Participant' . $this->typeName . 'Update'}($sisData, 'participant' . $this->typeName);
            unset($arr['days']);
            if (empty($arr)) {
                return;
            }
        }

        //@todo: check actionId privileges
        $payment = $this->getPayment($participantId, new Event($actionId, EventType::get($this->type === 'camp' ? EventType::CAMP : EventType::GENERAL)));

        foreach ($arr as $key => $value) {
            switch ($key) {
                case 'payment':
                    $payment->setPayment(MoneyFactory::fromFloat((float) $value));
                    break;
                case 'repayment':
                    $payment->setRepayment(MoneyFactory::fromFloat((float) $value));
                    break;
                case 'isAccount':
                    $payment->setAccount($value);
                    break;
                default:
                    throw new InvalidArgumentException(sprintf("Camp participant hasn't attribute '%s'", $key));
            }
        }
        $this->repository->save($payment);
    }

    public function removeParticipant(int $participantId) : void
    {
        try {
            $this->repository->remove($this->repository->find($participantId));
        } catch (PaymentNotFound $exc) {
        }
        $this->skautis->event->{'Participant' . $this->typeName . 'Delete'}(['ID' => $participantId, 'DeletePerson' => false]);
    }

    public function getTotalPayment(int $eventId) : float
    {
        return (float) array_reduce(
            $this->getAll($eventId),
            function ($res, ParticipantDTO $v) {
                return $res + $v->getPayment();
            }
        );
    }

    /**
     * vrací počet osobodní na dané akci
     *
     * @param ParticipantDTO[] $participants
     */
    public function getPersonsDays(array $participants) : int
    {
        $days = 0;
        foreach ($participants as $p) {
            $days += $p->getDays();
        }

        return $days;
    }

    /**
     * @return mixed[]
     */
    public function getEventStatistic(int $eventId) : array
    {
        $skautisData = $this->skautis->event->{'EventStatisticAllEventGeneral'}(['ID_EventGeneral' => $eventId]);

        $result = [];

        foreach ($skautisData as $row) {
            $result[$row->ID_ParticipantCategory] = $row;
        }

        return $result;
    }

    /**
     * @param string $isAccount 'Y' or 'N'
     */
    public function getCampTotalPayment(int $campId, string $category, string $isAccount) : float
    {
        $res = 0;
        /** @var \Model\DTO\Participant\Participant $p */
        foreach ($this->getAll($campId) as $p) {
            //pokud se alespon v jednom neshodují, tak pokracujte
            if (($category === 'adult' xor preg_match('/^Dospěl/', $p->getCategory()))
                || ($isAccount === 'Y' xor $p->getOnAccount() === 'Y')
            ) {
                continue;
            }
            $res += $p->getPayment();
        }

        return $res;
    }

    public function countPragueParticipants(stdClass $event) : ?PragueParticipants
    {
        if (! Strings::startsWith($event->RegistrationNumber, self::PRAGUE_UNIT_PREFIX)) {
            return null;
        }

        $eventStartDate    = new Date($event->StartDate);
        $participants      = $this->getAll($event->ID);
        $under18           = 0;
        $between18and26    = 0;
        $personDaysUnder26 = 0;
        $citizensCount     = 0;
        /** @var \Model\DTO\Participant\Participant $p */
        foreach ($participants as $p) {
            if (stripos($p->getCity(), 'Praha') === false) {
                continue;
            }
            $citizensCount += 1;

            $birthday = $p->getBirthday();

            if ($birthday === null) {
                continue;
            }

            $ageInYears = $eventStartDate->diffInYears($birthday);

            if ($ageInYears <= self::PRAGUE_SUPPORTABLE_AGE) {
                $under18 += 1;
            }

            if (self::PRAGUE_SUPPORTABLE_AGE < $ageInYears && $ageInYears <= self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                $between18and26 += 1;
            }

            if ($ageInYears > self::PRAGUE_SUPPORTABLE_UPPER_AGE) {
                continue;
            }

            $personDaysUnder26 += $p->getDays();
        }

        return new PragueParticipants($under18, $between18and26, $personDaysUnder26, $citizensCount);
    }

    private function getPayment(int $participantId, Event $event) : Payment
    {
        try {
            $payment = $this->repository->find($participantId);
        } catch (PaymentNotFound $exc) {
            $payment = PaymentFactory::createDefault($participantId, $event);
        }

        return $payment;
    }
}
