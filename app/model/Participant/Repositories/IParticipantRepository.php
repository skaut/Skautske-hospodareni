<?php

declare(strict_types=1);

namespace Model\Participant\Repositories;

use Model\Participant\Event;
use Model\Participant\Participant;

interface IParticipantRepository
{
    /**
     * @return Participant[]
     */
    public function findByEvent(Event $event) : array;
}
