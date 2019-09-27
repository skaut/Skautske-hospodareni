<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Event\SkautisEventId;

/**
 * @see AddEventParticipantHandler
 */
final class AddEventParticipant
{
    /** @var SkautisEventId */
    private $eventId;

    /** @var int */
    private $participantId;

    public function __construct(SkautisEventId $eventId, int $participantId)
    {
        $this->eventId       = $eventId;
        $this->participantId = $participantId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }
}
