<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\ChronosDate;
use Model\Common\UnitId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property-read SkautisCampId $id
 * @property-read string $displayName
 * @property-read UnitId $unitId
 * @property-read string $unitName
 * @property-read Date $startDate
 * @property-read Date $endDate
 * @property-read string $location
 * @property-read string $state
 * @property-read string $registrationNumber
 */
class Camp implements ISkautisEvent
{
    use SmartObject;

    public const STATE_APPROVED_PARENT = 'approvedParent';
    public const STATE_REAL            = 'real';

    /** @param  UnitId[] $participatingUnits */
    public function __construct(
        private SkautisCampId              $id,
        private string                     $displayName,
        private UnitId                     $unitId,
        private string                     $unitName,
        private ChronosDate                $startDate,
        private ChronosDate                $endDate,
        private string                     $location,
        private string                     $state,
        private string                     $registrationNumber,
        private array                      $participatingUnits,
        private bool                       $isOnlineLogin,
        private int|null                   $totalDays = null,
        private ParticipantStatistics|null $participantStatistics = null,
        private bool|null                  $realAutoComputed = null,
        private bool|null                  $realTotalCostAutoComputed = null,
    ) {
    }

    public function getId(): SkautisCampId
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

    public function getState(): string
    {
        return $this->state;
    }

    public function getStartDate(): ChronosDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ChronosDate
    {
        return $this->endDate;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    /** @return UnitId[] */
    public function getParticipatingUnits(): array
    {
        return $this->participatingUnits;
    }

    public function getTotalDays(): int|null
    {
        return $this->totalDays;
    }

    public function getParticipantStatistics(): ParticipantStatistics|null
    {
        return $this->participantStatistics;
    }

    public function isRealAutoComputed(): bool|null
    {
        return $this->realAutoComputed;
    }

    public function isRealTotalCostAutoComputed(): bool|null
    {
        return $this->realTotalCostAutoComputed;
    }

    public function isOnlineLogin(): bool
    {
        return $this->isOnlineLogin;
    }
}
