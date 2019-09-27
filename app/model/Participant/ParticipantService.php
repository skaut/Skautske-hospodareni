<?php

declare(strict_types=1);

namespace Model;

use InvalidArgumentException;
use Model\DTO\Participant\Participant as ParticipantDTO;
use Model\DTO\Payment\ParticipantFactory as ParticipantDTOFactory;
use Model\Participant\Payment;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\PaymentNotFound;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Skautis\Factory\ParticipantFactory;
use Model\Utils\MoneyFactory;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use function array_key_exists;
use function preg_match;
use function sprintf;

class ParticipantService extends MutableBaseService
{
    /** @var IPaymentRepository */
    private $repository;

    public function __construct(string $name, Skautis $skautIS, IPaymentRepository $repository)
    {
        parent::__construct($name, $skautIS);
        $this->repository = $repository;
    }

    public function get(int $participantId, int $actionId) : ParticipantDTO
    {
        $data = $this->skautis->event->{'Participant' . $this->typeName . 'Detail'}(['ID' => $participantId]);

        return ParticipantDTOFactory::create(
            ParticipantFactory::create($data, $this->getPayment($participantId, new Event($actionId, EventType::get($this->type))))
        );
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
            $this->repository->remove($this->repository->findByParticipant($participantId, EventType::get($this->type)));
        } catch (PaymentNotFound $exc) {
        }
        $this->skautis->event->{'Participant' . $this->typeName . 'Delete'}(['ID' => $participantId, 'DeletePerson' => false]);
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

    private function getPayment(int $participantId, Event $event) : Payment
    {
        try {
            $payment = $this->repository->findByParticipant($participantId, EventType::get($this->type));
        } catch (PaymentNotFound $exc) {
            $payment = PaymentFactory::createDefault($participantId, $event);
        }

        return $payment;
    }
}
