<?php

declare(strict_types=1);

namespace Model\Events\Events;

use Model\Event\SkautisEventId;

class BaseEvent
{
    private SkautisEventId $eventId;

    private int $unitId;

    private string $eventName;

    public function __construct(SkautisEventId $eventId, int $unitId, string $eventName)
    {
        $this->eventId   = $eventId;
        $this->unitId    = $unitId;
        $this->eventName = $eventName;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getEventName() : string
    {
        return $this->eventName;
    }
}
