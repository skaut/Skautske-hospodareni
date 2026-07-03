<?php

declare(strict_types=1);

namespace App\Model\Common\Repositories;

use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\DTO\Participant\Participant;
use App\Model\DTO\Participant\PaymentDetails;
use App\Model\Event\SkautisCampId;
use App\Model\Event\SkautisEducationId;
use App\Model\Event\SkautisEventId;

interface IParticipantRepository
{
    /** @return Participant[] */
    public function findByEvent(SkautisEventId $id): array;

    /** @return Participant[] */
    public function findByCamp(SkautisCampId $id): array;

    /** @return PaymentDetails[] */
    public function findByPaymentDetail(SkautisCampId $id): array;

    /** @return Participant[] */
    public function findByEducation(SkautisEducationId $id): array;

    public function addCampParticipant(SkautisCampId $campId, int $personId): void;

    public function addEventParticipant(SkautisEventId $eventId, int $personId): void;

    public function createCampParticipant(SkautisCampId $eventId, NonMemberParticipant $participant): void;

    public function createEventParticipant(SkautisEventId $eventId, NonMemberParticipant $participant): void;

    public function removeCampParticipant(int $participantId): void;

    public function removeEventParticipant(int $participantId): void;
}
