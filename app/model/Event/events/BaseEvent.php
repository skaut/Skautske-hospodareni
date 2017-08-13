<?php

namespace Model\Events\Events;

class BaseEvent
{
    /** @var int */
    private $eventId;

    /** @var int */
    private $unitId;

    /** @var string */
    private $eventName;

    public function __construct(int $eventId, int $unitId, string $eventName)
    {
        $this->eventId = $eventId;
        $this->unitId = $unitId;
        $this->eventName = $eventName;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

}
