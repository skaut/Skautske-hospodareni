<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property-read string $displayName
 * @property-read Date $startDate
 * @property-read Date $endDate
 */
class Education implements ISkautisEvent
{
    use SmartObject;

    private SkautisEducationId $id;

    private string $displayName;

    private UnitId $unitId;

    private string $unitName;

    private Date $startDate;

    private Date $endDate;

    private string $location;

    private string $state;

    public function __construct(
        SkautisEducationId $id,
        string $displayName,
        UnitId $unitId,
        string $unitName,
        Date $startDate,
        Date $endDate,
        string $location,
        string $state
    ) {
        $this->id          = $id;
        $this->displayName = $displayName;
        $this->unitId      = $unitId;
        $this->unitName    = $unitName;
        $this->startDate   = $startDate;
        $this->endDate     = $endDate;
        $this->location    = $location;
        $this->state       = $state;
    }

    public function getId(): SkautisEducationId
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
    {
        return $this->endDate;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getState(): string
    {
        return $this->state;
    }
}
