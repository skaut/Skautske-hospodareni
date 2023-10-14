<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Grant\SkautisGrantId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property-read SkautisEducationId $id
 * @property-read string $displayName
 * @property-read UnitId $unitId
 * @property-read string $unitName
 * @property-read string $unitRegistrationNumber
 * @property-read Date|null $startDate
 * @property-read Date|null $endDate
 * @property-read string $location
 * @property-read string $state
 * @property-read SkautisGrantId|null $grantId
 */
class Education implements ISkautisEvent
{
    use SmartObject;

    public function __construct(
        private SkautisEducationId $id,
        private string $displayName,
        private UnitId $unitId,
        private string $unitName,
        private string $unitRegistrationNumber,
        private Date|null $startDate,
        private Date|null $endDate,
        private string $location,
        private string $state,
        private SkautisGrantId|null $grantId,
    ) {
    }

    public function getId(): SkautisEducationId
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function getUnitRegistrationNumber(): string
    {
        return $this->unitRegistrationNumber;
    }

    public function getStartDate(): Date|null
    {
        return $this->startDate;
    }

    public function getEndDate(): Date|null
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

    public function getGrantId(): SkautisGrantId|null
    {
        return $this->grantId;
    }
}
