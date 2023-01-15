<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Model\Common\UnitId;
use Model\Skautis\ISkautisEvent;
use Nette\SmartObject;

/**
 * @property-read SkautisEventId $id
 * @property-read string $displayName
 * @property-read UnitId $unitId
 * @property-read string $unitName
 * @property-read string $state
 * @property-read Date $startDate
 * @property-read Date $endDate
 * @property-read int $totalDays
 * @property-read string $location
 * @property-read string $registrationNumber
 * @property-read string $note
 * @property-read int $scopeId
 * @property-read int $typeId
 */
class Event implements ISkautisEvent
{
    use SmartObject;

    private string $location;

    private string $note;

    private bool|null $statisticAutoComputed = null;

    public function __construct(
        private SkautisEventId $id,
        private string $displayName,
        private UnitId $unitId,
        private string $unitName,
        private string $state,
        private Date $startDate,
        private Date $endDate,
        private int|null $totalDays = null,
        string|null $location,
        private string $registrationNumber,
        string|null $note,
        private int $scopeId,
        private int $typeId,
        bool|null $isStatisticAutoComputed,
        private int|null $realCount = null,
        private int|null $realChildDays = null,
        private int|null $realPersonDays = null,
        private string|null $personClosed = null,
        private Date|null $dateClosed = null,
        private string|null $unitEducativeName = null,
    ) {
        $this->location              = $location ?? '';
        $this->note                  = $note ?? '';
        $this->statisticAutoComputed = $isStatisticAutoComputed;
    }

    public function update(
        string $displayName,
        string|null $location,
        Date $startDate,
        Date $endDate,
        int $scopeId,
        int $typeId,
    ): void {
        $this->displayName = $displayName;
        $this->location    = $location;
        $this->startDate   = $startDate;
        $this->endDate     = $endDate;
        $this->scopeId     = $scopeId;
        $this->typeId      = $typeId;
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
        return $this->state === 'draft';
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getStartDate(): Date
    {
        return $this->startDate;
    }

    public function getEndDate(): Date
    {
        return $this->endDate;
    }

    public function getTotalDays(): int|null
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

    public function isStatisticAutoComputed(): bool|null
    {
        return $this->statisticAutoComputed;
    }

    public function getRealCount(): int|null
    {
        return $this->realCount;
    }

    public function getRealChildDays(): int|null
    {
        return $this->realChildDays;
    }

    public function getRealPersonDays(): int|null
    {
        return $this->realPersonDays;
    }

    public function getPersonClosed(): string|null
    {
        return $this->personClosed;
    }

    public function getDateClosed(): Date|null
    {
        return $this->dateClosed;
    }

    public function getUnitEducativeName(): string|null
    {
        return $this->unitEducativeName;
    }
}
