<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Event;

use App\Model\Event\Handlers\Event\CreateEventHandler;
use Cake\Chronos\ChronosDate;

/** @see CreateEventHandler */
final class CreateEvent
{
    public function __construct(
        private string $name,
        private ChronosDate $startDate,
        private ChronosDate $endDate,
        private int $unitId,
        private ?string $location,
        private int $scopeId,
        private int $typeId,
    ) {
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

    public function getUnitId(): int
    {
        return $this->unitId;
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
