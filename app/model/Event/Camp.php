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

    /** @var SkautisCampId */
    private $id;

    /** @var string */
    private $displayName;

    /** @var UnitId */
    private $unitId;

    /** @var string */
    private $unitName;

    /** @var Date */
    private $startDate;

    /** @var Date */
    private $endDate;

    /** @var string */
    private $location;

    /** @var string */
    private $state;

    /** @var string */
    private $registrationNumber;

    /** @var UnitId[] */
    private $participatingUnits;

    /** @var int */
    private $totalDays;

    /** @var int */
    private $realAdult;

    /** @var int */
    private $realChild;

    /** @var int */
    private $realCount;

    /** @var int */
    private $realChildDays;

    /** @var int */
    private $realPersonDays;

    /** @var bool */
    private $realAutoComputed;

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
        int $totalDays,
        int $realAdult,
        int $realChild,
        int $realCount,
        int $realChildDays,
        int $realPersonDays,
        bool $realAutoComputed
    ) {
        $this->id                 = $id;
        $this->displayName        = $displayName;
        $this->unitId             = $unitId;
        $this->unitName           = $unitName;
        $this->startDate          = $startDate;
        $this->endDate            = $endDate;
        $this->location           = $location;
        $this->state              = $state;
        $this->registrationNumber = $registrationNumber;
        $this->participatingUnits = $participatingUnits;
        $this->totalDays          = $totalDays;
        $this->realAdult          = $realAdult;
        $this->realChild          = $realChild;
        $this->realCount          = $realCount;
        $this->realChildDays      = $realChildDays;
        $this->realPersonDays     = $realPersonDays;
        $this->realAutoComputed   = $realAutoComputed;
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

    public function getTotalDays() : int
    {
        return $this->totalDays;
    }

    public function getRealAdult() : int
    {
        return $this->realAdult;
    }

    public function getRealChild() : int
    {
        return $this->realChild;
    }

    public function getRealCount() : int
    {
        return $this->realCount;
    }

    public function getRealChildDays() : int
    {
        return $this->realChildDays;
    }

    public function getRealPersonDays() : int
    {
        return $this->realPersonDays;
    }

    public function isRealAutoComputed() : bool
    {
        return $this->realAutoComputed;
    }
}
