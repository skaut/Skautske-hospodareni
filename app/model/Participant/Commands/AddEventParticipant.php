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
    private $personId;

    public function __construct(SkautisEventId $eventId, int $personId)
    {
        $this->eventId  = $eventId;
        $this->personId = $personId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getPersonId() : int
    {
        return $this->personId;
    }
}
