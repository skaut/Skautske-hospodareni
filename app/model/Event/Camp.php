<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\ChronosDate;
use Model\Common\UnitId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property SkautisCampId $id
 * @property string        $displayName
 * @property UnitId        $unitId
 * @property string        $unitName
 * @property ChronosDate   $startDate
 * @property ChronosDate   $endDate
 * @property string        $location
 * @property string        $state
 * @property string        $registrationNumber
 */
class Camp implements ISkautisEvent
{
    use SmartObject;

    /** @param  UnitId[] $participatingUnits */
    public function __construct(
        private SkautisCampId $id,
        private string $displayName,
        private UnitId $unitId,
        private string $unitName,
        private ChronosDate $startDate,
        private ChronosDate $endDate,
        private string $location,
        private string $state,
        private string $registrationNumber,
        private array $participatingUnits,
        private bool $isOnlineLogin,
        private ?int $totalDays = null,
        private ?ParticipantStatistics $participantStatistics = null,
        private ?bool $realAutoComputed = null,
        private ?bool $realTotalCostAutoComputed = null,
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

    public function getTotalDays(): ?int
    {
        return $this->totalDays;
    }

    public function getParticipantStatistics(): ?ParticipantStatistics
    {
        return $this->participantStatistics;
    }

    public function isRealAutoComputed(): ?bool
    {
        return $this->realAutoComputed;
    }

    public function isRealTotalCostAutoComputed(): ?bool
    {
        return $this->realTotalCostAutoComputed;
    }

    public function isOnlineLogin(): bool
    {
        return $this->isOnlineLogin;
    }
}
