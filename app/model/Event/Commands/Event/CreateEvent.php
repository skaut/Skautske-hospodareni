<?php

namespace Model\Event\Commands\Event;

use Cake\Chronos\Date;
use Model\Event\Handlers\Event\CreateEventHandler;

/**
 * @see CreateEventHandler
 */
final class CreateEvent
{

    /** @var string */
    private $name;

    /** @var Date */
    private $startDate;

    /** @var Date */
    private $endDate;

    /** @var int */
    private $unitId;

    /** @var string|NULL */
    private $location;

    /** @var int */
    private $scopeId;

    /** @var int */
    private $typeId;

    public function __construct(
        string $name,
        Date $startDate,
        Date $endDate,
        int $unitId,
        ?string $location,
        int $scopeId,
        int $typeId
    )
    {
        $this->name = $name;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->unitId = $unitId;
        $this->location = $location;
        $this->scopeId = $scopeId;
        $this->typeId = $typeId;
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
