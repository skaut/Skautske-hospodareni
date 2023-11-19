<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Cake\Chronos\ChronosDate;
use Model\Event\Handlers\Event\UpdateEventHandler;
use Model\Event\SkautisEventId;

/** @see UpdateEventHandler */
class UpdateEvent
{
    public function __construct(private SkautisEventId $eventId, private string $name, private ChronosDate $startDate, private ChronosDate $endDate, private string|null $location = null, private int $scopeId, private int $typeId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): ChronosDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ChronosDate
    {
        return $this->endDate;
    }

    public function getLocation(): string|null
    {
        return $this->location;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }
}
