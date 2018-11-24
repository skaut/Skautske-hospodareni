<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Participant\EventType;

/**
 * @see PersonDaysQueryHandler
 */
final class PersonDaysQuery
{
    /** @var EventType */
    private $eventType;

    /** @var int */
    private $eventId;

    public function __construct(EventType $eventType, int $eventId)
    {
        $this->eventType = $eventType;
        $this->eventId   = $eventId;
    }

    public function getEventType() : EventType
    {
        return $this->eventType;
    }

    public function getEventId() : int
    {
        return $this->eventId;
    }
}
