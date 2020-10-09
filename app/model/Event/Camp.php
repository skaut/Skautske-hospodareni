<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
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

    private SkautisCampId $id;

    private string $displayName;

    private UnitId $unitId;

    private string $unitName;

    private Date $startDate;

    private Date $endDate;

    private string $location;

    private string $state;

    private string $registrationNumber;

    /** @var UnitId[] */
    private array $participatingUnits;

    private ?int $totalDays;

    private ?ParticipantStatistics $participantStatistics;

    private ?bool $realAutoComputed;

    private ?bool $realTotalCostAutoComputed;

    /**
     * @param  UnitId[] $participatingUnits
     */
    public function __construct(
        SkautisCampId $id,
        string $displayName,
        UnitId $unitId,
        string $unitName,
        Date $startDate,
        Date $endDate,
        string $location,
        string $state,
        string $registrationNumber,
        array $participatingUnits,
        ?int $totalDays,
        ?ParticipantStatistics $participantStatistics,
        ?bool $realAutoComputed,
        ?bool $realTotalCostAutoComputed
    ) {
        $this->id                        = $id;
        $this->displayName               = $displayName;
        $this->unitId                    = $unitId;
        $this->unitName                  = $unitName;
        $this->startDate                 = $startDate;
        $this->endDate                   = $endDate;
        $this->location                  = $location;
        $this->state                     = $state;
        $this->registrationNumber        = $registrationNumber;
        $this->participatingUnits        = $participatingUnits;
        $this->totalDays                 = $totalDays;
        $this->participantStatistics     = $participantStatistics;
        $this->realAutoComputed          = $realAutoComputed;
        $this->realTotalCostAutoComputed = $realTotalCostAutoComputed;
    }

    public function getId() : SkautisCampId
    {
        return $this->id;
    }

    public function getDisplayName() : string
    {
        return $this->displayName;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getUnitName() : string
    {
        return $this->unitName;
    }

    public function getState() : string
    {
        return $this->state;
    }

    public function getStartDate() : Date
    {
        return $this->startDate;
    }

    public function getEndDate() : Date
    {
        return $this->endDate;
    }

    public function getLocation() : string
    {
        return $this->location;
    }

    public function getRegistrationNumber() : string
    {
        return $this->registrationNumber;
    }

    /**
     * @return UnitId[]
     */
    public function getParticipatingUnits() : array
    {
        return $this->participatingUnits;
    }

    public function getTotalDays() : ?int
    {
        return $this->totalDays;
    }

    public function getParticipantStatistics() : ?ParticipantStatistics
    {
        return $this->participantStatistics;
    }

    public function isRealAutoComputed() : ?bool
    {
        return $this->realAutoComputed;
    }

    public function isRealTotalCostAutoComputed() : ?bool
    {
        return $this->realTotalCostAutoComputed;
    }
}
