<?php

declare(strict_types=1);

namespace Model\Events\Events;

use Model\Event\SkautisEventId;

class BaseEvent
{
    public function __construct(private SkautisEventId $eventId, private int $unitId, private string $eventName)
    {
    }

    public function getEventId(): SkautisEventId
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
