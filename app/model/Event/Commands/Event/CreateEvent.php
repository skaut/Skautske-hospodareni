<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Cake\Chronos\Date;
use Model\Event\Handlers\Event\CreateEventHandler;

/** @see CreateEventHandler */
final class CreateEvent
{
    public function __construct(
        private string $name,
        private Date $startDate,
        private Date $endDate,
        private int $unitId,
        private string|null $location = null,
        private int $scopeId,
        private int $typeId,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
    {
        return $this->endDate;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
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
