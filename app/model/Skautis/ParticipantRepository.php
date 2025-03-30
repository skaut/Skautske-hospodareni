<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Common\Repositories\IParticipantRepository;
use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant as ParticipantDTO;
use model\DTO\Participant\PaymentDetails;
use Model\DTO\Payment\ParticipantFactory as ParticipantDTOFactory;
use model\Event\Exception\CampInvitationNotFound;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEducationId;
use Model\Event\SkautisEventId;
use Model\Participant\Participant;
use Model\Participant\Payment\Event;
use Model\Participant\Payment\EventType;
use Model\Participant\PaymentFactory;
use Model\Participant\Repositories\IPaymentRepository;
use Model\Skautis\Factory\ParticipantFactory;
use Skautis\Skautis;
use Skautis\Wsdl\PermissionException;
use Skautis\Wsdl\WsdlException;
use stdClass;

use function array_diff_key;
use function array_key_exists;
use function array_map;
use function is_array;
use function preg_match;
use function strcoll;
use function usort;

final class ParticipantRepository implements IParticipantRepository
{
    private const DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(private Skautis $skautis, private IPaymentRepository $payments)
    {
    }

    /** @return ParticipantDTO[] */
    public function findByEvent(SkautisEventId $id): array
    {
        $participants = $this->skautis->event->ParticipantGeneralAll(['ID_EventGeneral' => $id->toInt()]);
        if (! is_array($participants)) {
            return []; // API returns empty object when there are no results
        }

        $event = new Event($id->toInt(), EventType::GENERAL());

        return $this->processParticipants($participants, $event);
    }

    /** @return ParticipantDTO[] */
    public function findByCamp(SkautisCampId $id): array
    {
        $participants = $this->skautis->event->ParticipantCampAll(['ID_EventCamp' => $id->toInt()]);
        if (! is_array($participants)) {
            return []; // API returns empty object when there are no results
        }

        $event = new Event($id->toInt(), EventType::CAMP());

        return $this->processParticipants($participants, $event);
    }

    /**
     * @return PaymentDetails[]
     *
     * @throws CampInvitationNotFound
     */
    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function findByPaymentDetail(SkautisCampId $id): array
    {
        try {
            $campInvitationPerson = $this->skautis->event->EventCampInvitationAll(['ID_EventCamp' => $id->toInt()]);
            $invitations          = [];
            foreach ($campInvitationPerson as $invitation) {
                $invitations[$invitation->ID] = $invitation;
            }

            $campEnrollPerson = $this->skautis->event->EventCampEnrollAll(['ID_EventCamp' => $id->toInt()]);
            $paymentDetails   = [];
            foreach ($campEnrollPerson as $person) {
                if (! isset($invitations[$person->ID_EventCampInvitation])) {
                    continue;
                }

                $invitation                         = $invitations[$person->ID_EventCampInvitation];
                $paymentDetails[$person->ID_Person] = new PaymentDetails(
                    $person->ID_Person,
                    $person->VariableSymbol ?? '',
                    (float) $invitation->Price,
                    $invitation->PaymentNote,
                    $invitation->SpecificSymbol,
                    $invitation->PaymentTerm,
                );
            }

            return $paymentDetails;
        } catch (PermissionException) {
            throw new CampInvitationNotFound();
        }
    }

    /** @return ParticipantDTO[] */
    public function findByEducation(SkautisEducationId $id): array
    {
        $participants = $this->skautis->event->ParticipantEducationAll(['ID_EventEducation' => $id->toInt(), 'IsActive' => true]);
        if (! is_array($participants)) {
            return []; // API returns empty object when there are no results
        }

        $event = new Event($id->toInt(), EventType::EDUCATION());

        return $this->processParticipants($participants, $event);
    }

    public function addCampParticipant(SkautisCampId $campId, int $personId): void
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

    public function addEventParticipant(SkautisEventId $eventId, int $personId): void
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

    // phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
    public function createEventParticipant(SkautisEventId $eventId, NonMemberParticipant $participant): void
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

    public function createCampParticipant(SkautisCampId $eventId, NonMemberParticipant $participant): void
    {
        $newParticipantArr = $this->skautis->event->ParticipantCampInsert([
            'ID_EventCamp' => $eventId->toInt(),
            'Person' => [
                'FirstName' => $participant->getFirstName(),
                'LastName' => $participant->getLastName(),
                'NickName' => $participant->getNickName(),
                'Note' => '',
                'IdentificationCode' => '',
                'IsForeign' => false,
            ],
        ]);
        $this->fillParticipantInfo($newParticipantArr->ID_Person, $participant);
    }

    public function removeEventParticipant(int $participantId): void
    {
        $this->skautis->event->ParticipantGeneralDelete(['ID' => $participantId, 'DeletePerson' => false]);
    }

    public function removeCampParticipant(int $participantId): void
    {
        $this->skautis->event->ParticipantCampDelete(['ID' => $participantId, 'DeletePerson' => false]);
    }

    private function fillParticipantInfo(int $participantId, NonMemberParticipant $participant): void
    {
        $this->skautis->org->PersonUpdateBasic([
            'ID' => $participantId,
            'FirstName' => $participant->getFirstName(),
            'LastName' => $participant->getLastName(),
            'IdentificationCode' => null,
            'Birthday' => $participant->getBirthday()?->format(self::DATETIME_FORMAT),
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
    private function processParticipants(array $participantsSis, Event $event): array
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
            fn (Participant $one, Participant $two) => strcoll($one->getDisplayName(), $two->getDisplayName())
        );

        return array_map([ParticipantDTOFactory::class, 'create'], $participants);
    }
}
