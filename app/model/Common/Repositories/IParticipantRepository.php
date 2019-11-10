<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\DTO\Participant\NonMemberParticipant;
use Model\DTO\Participant\Participant;
use Model\Event\SkautisCampId;
use Model\Event\SkautisEventId;

interface IParticipantRepository
{
    /**
     * @return Participant[]
     */
    public function findByEvent(SkautisEventId $id) : array;

    /**
     * @return Participant[]
     */
    public function findByCamp(SkautisCampId $id) : array;

    public function addCampParticipant(SkautisCampId $campId, int $personId) : void;

    public function addEventParticipant(SkautisEventId $eventId, int $personId) : void;

    public function createCampParticipant(SkautisCampId $eventId, NonMemberParticipant $participant) : void;

    public function createEventParticipant(SkautisEventId $eventId, ParticipantCreation $participant) : void;

    public function removeCampParticipant(int $participantId) : void;

    public function removeEventParticipant(int $participantId) : void;

}
