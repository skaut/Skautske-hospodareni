<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant as ParticipantDTO;
use Model\DTO\Payment\ParticipantFactory as ParticipantDTOFactory;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;
use Model\Participant\Participant;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Services\Language;
use Model\Skautis\Factory\ParticipantFactory;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use stdClass;
use function array_diff_key;
use function array_key_exists;
use function array_map;
use function is_array;
use function preg_match;
use function usort;

final class ParticipantRepository implements IParticipantRepository
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    /** @var Skautis */
    private $skautis;

    /** @var IPaymentRepository */
    private $payments;

    public function __construct(Skautis $skautis, IPaymentRepository $payments)
    {
        $this->skautis  = $skautis;
        $this->payments = $payments;
    }

    /**
     * @return ParticipantDTO[]
     */
    public function findByEvent(SkautisEventId $id) : array
    {
        $participants = $this->skautis->event->ParticipantGeneralAll(['ID_EventGeneral' => $id->toInt()]);
        if (! is_array($participants)) {
            return []; // API returns empty object when there are no results
        }
        $event = new Event($id->toInt(), EventType::GENERAL());

        return $this->processParticipants($participants, $event);
    }

    /**
     * @return ParticipantDTO[]
     */
    public function findByCamp(SkautisCampId $id) : array
    {
        $participants = $this->skautis->event->ParticipantCampAll(['ID_EventCamp' => $id->toInt()]);
        if (! is_array($participants)) {
            return []; // API returns empty object when there are no results
        }
        $event = new Event($id->toInt(), EventType::CAMP());

        return $this->processParticipants($participants, $event);
    }

    public function addCampParticipant(SkautisCampId $campId, int $personId) : void
    {
        try {
            $this->skautis->event->ParticipantCampInsert([
                'ID_EventCamp' => $campId->toInt(),
                'ID_Person' => $personId,
            ]);
        } catch (WsdlException $ex) {
            if (! preg_match('/Chyba validace \(Participant_PersonIsAllreadyParticipant\)/', $ex->getMessage())) {
                throw $ex;
            }
        }
    }

    public function addEventParticipant(SkautisEventId $eventId, int $personId) : void
    {
        try {
            $this->skautis->event->ParticipantGeneralInsert([
                'ID_EventGeneral' => $eventId->toInt(),
                'ID_Person' => $personId,
            ]);
        } catch (WsdlException $ex) {
            if (! preg_match('/Chyba validace \(Participant_PersonIsAllreadyParticipantGeneral\)/', $ex->getMessage())) {
                throw $ex;
            }
        }
    }

    public function createEventParticipant(SkautisEventId $eventId, NonMemberParticipant $participant) : void
    {
        $newParticipantArr = $this->skautis->event->ParticipantGeneralInsert([
            'ID_EventGeneral' => $eventId->toInt(),
            'Person' => [
                'FirstName' => $participant->getFirstName(),
                'LastName' => $participant->getLastName(),
                'NickName' => $participant->getNickName(),
                'Note' => '',
            ],
        ]);
        $this->fillParticipantInfo($newParticipantArr->ID_Person, $participant);
    }

    public function createCampParticipant(SkautisCampId $eventId, NonMemberParticipant $participant) : void
    {
        $newParticipantArr = $this->skautis->event->ParticipantCampInsert([
            'ID_EventCamp' => $eventId->toInt(),
            'Person' => [
                'FirstName' => $participant->getFirstName(),
                'LastName' => $participant->getLastName(),
                'NickName' => $participant->getNickName(),
                'Note' => '',
            ],
        ]);
        $this->fillParticipantInfo($newParticipantArr->ID_Person, $participant);
    }

    public function removeEventParticipant(int $participantId) : void
    {
        $this->skautis->event->ParticipantGeneralDelete(['ID' => $participantId, 'DeletePerson' => false]);
    }

    public function removeCampParticipant(int $participantId) : void
    {
        $this->skautis->event->ParticipantCampDelete(['ID' => $participantId, 'DeletePerson' => false]);
    }

    private function fillParticipantInfo(int $participantId, NonMemberParticipant $participant) : void
    {
        $this->skautis->org->PersonUpdateBasic([
            'ID' => $participantId,
            'FirstName' => $participant->getFirstName(),
            'LastName' => $participant->getLastName(),
            'IdentificationCode' => null,
            'Birthday' => $participant->getBirthday() !== null ? $participant->getBirthday()->format(self::DATETIME_FORMAT) : '',
            'Street' => $participant->getStreet(),
            'City' => $participant->getCity(),
            'Postcode' => $participant->getPostcode(),
        ]);
    }

    /**
     * @param stdClass[] $participantsSis
     *
     * @return ParticipantDTO[]
     */
    private function processParticipants(array $participantsSis, Event $event) : array
    {
        $participantPayments = $this->payments->findByEvent($event);
        $participants        = [];
        foreach ($participantsSis as $p) {
            if (array_key_exists($p->ID, $participantPayments)) {
                $payment =  $participantPayments[$p->ID];
            } else {
                $payment =  PaymentFactory::createDefault($p->ID, $event);
            }
            $participants[$p->ID] = ParticipantFactory::create($p, $payment);
        }

        foreach (array_diff_key($participantPayments, $participants) as $paymentToRemove) {
            $this->payments->remove($paymentToRemove); //delete zaznam, protoze neexistuje k nemu ucastnik
        }

        usort(
            $participants,
            function (Participant $one, Participant $two) : int {
                return Language::compare($one->getDisplayName(), $two->getDisplayName());
            }
        );

        return array_map([ParticipantDTOFactory::class, 'create'], $participants);
    }
}
