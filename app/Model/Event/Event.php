<?php

declare(strict_types=1);

namespace App\Model\Event;

use App\Model\Common\UnitId;
use App\Model\Event\Enum\EventState;
use App\Model\Skautis\ISkautisEvent;
use Cake\Chronos\ChronosDate;
use Nette\SmartObject;

/**
 * @property SkautisEventId $id
 * @property string         $displayName
 * @property UnitId         $unitId
 * @property string         $unitName
 * @property string         $state
 * @property ChronosDate    $startDate
 * @property ChronosDate    $endDate
 * @property int            $totalDays
 * @property string         $location
 * @property string         $registrationNumber
 * @property string         $note
 * @property int            $scopeId
 * @property int            $typeId
 */
class Event implements ISkautisEvent
{
    use SmartObject;

    private string $location;

    private string $note;

    private ?bool $statisticAutoComputed = null;

    public function __construct(
        private SkautisEventId $id,
        private string $displayName,
        private UnitId $unitId,
        private string $unitName,
        private string $state,
        private ChronosDate $startDate,
        private ChronosDate $endDate,
        private ?int $totalDays,
        ?string $location,
        private string $registrationNumber,
        ?string $note,
        private int $scopeId,
        private int $typeId,
        ?bool $isStatisticAutoComputed,
        private ?int $realCount = null,
        private ?int $realChildDays = null,
        private ?int $realPersonDays = null,
        private ?string $personClosed = null,
        private ?ChronosDate $dateClosed = null,
        private ?string $unitEducativeName = null,
    ) {
        $this->location = $location ?? '';
        $this->note = $note ?? '';
        $this->statisticAutoComputed = $isStatisticAutoComputed;
    }

    public function update(
        string $displayName,
        ?string $location,
        ChronosDate $startDate,
        ChronosDate $endDate,
        int $scopeId,
        int $typeId,
    ): void {
        $this->displayName = $displayName;
        $this->location = $location ?? '';
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->scopeId = $scopeId;
        $this->typeId = $typeId;
    }

    public function getId(): SkautisEventId
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

    public function isOpen(): bool
    {
        return $this->state === EventState::DRAFT->value;
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

    public function getTotalDays(): ?int
    {
        return $this->totalDays;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getScopeId(): int
    {
        return $this->scopeId;
    }

    public function getTypeId(): int
    {
        return $this->typeId;
    }

    public function isStatisticAutoComputed(): ?bool
    {
        return $this->statisticAutoComputed;
    }

    public function getRealCount(): ?int
    {
        return $this->realCount;
    }

    public function getRealChildDays(): ?int
    {
        return $this->realChildDays;
    }

    public function getRealPersonDays(): ?int
    {
        return $this->realPersonDays;
    }

    public function getPersonClosed(): ?string
    {
        return $this->personClosed;
    }

    public function getDateClosed(): ?ChronosDate
    {
        return $this->dateClosed;
    }

    public function getUnitEducativeName(): ?string
    {
        return $this->unitEducativeName;
    }
}
