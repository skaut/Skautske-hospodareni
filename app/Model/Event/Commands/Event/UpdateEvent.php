<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Event;

use App\Model\Event\Handlers\Event\UpdateEventHandler;
use App\Model\Event\SkautisEventId;
use Cake\Chronos\ChronosDate;

/** @see UpdateEventHandler */
class UpdateEvent
{
    public function __construct(
        private SkautisEventId $eventId,
        private string $name,
        private ChronosDate $startDate,
        private ChronosDate $endDate,
        private ?string $location,
        private int $scopeId,
        private int $typeId,
    ) {
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

    public function getLocation(): ?string
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
