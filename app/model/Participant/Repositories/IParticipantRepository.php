<?php

declare(strict_types=1);

namespace Model\Participant\Repositories;

use Model\Participant\EventType;
use Model\Participant\Participant;

interface IParticipantRepository
{
    /**
     * @return Participant[]
     */
    public function findByEvent(EventType $type, int $eventId) : array;
}
