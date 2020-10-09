<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\DTO\Participant\NonMemberParticipant;
use Model\Event\SkautisEventId;

/**
 * @see CreateEventParticipantHandler
 */
final class CreateEventParticipant
{
    private SkautisEventId $eventId;

    private NonMemberParticipant $participant;

    public function __construct(SkautisEventId $eventId, NonMemberParticipant $participant)
    {
        $this->eventId     = $eventId;
        $this->participant = $participant;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getParticipant() : NonMemberParticipant
    {
        return $this->participant;
    }
}
