<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\ChronosDate;
use Model\Common\UnitId;
use Model\Grant\SkautisGrantId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property SkautisEducationId  $id
 * @property string              $displayName
 * @property UnitId              $unitId
 * @property string              $unitName
 * @property string              $unitRegistrationNumber
 * @property ChronosDate|null    $startDate
 * @property ChronosDate|null    $endDate
 * @property string              $location
 * @property string              $state
 * @property SkautisGrantId|null $grantId
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
        private ?ChronosDate $startDate,
        private ?ChronosDate $endDate,
        private string $location,
        private string $state,
        private ?SkautisGrantId $grantId,
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

    public function getStartDate(): ?ChronosDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ?ChronosDate
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

    public function getGrantId(): ?SkautisGrantId
    {
        return $this->grantId;
    }
}
