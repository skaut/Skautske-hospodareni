<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Nette\SmartObject;

/**
 * @property-read SkautisEventId $id
 * @property-read string $displayName
 * @property-read int $unitId
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
class Event
{
    use SmartObject;

    /** @var SkautisEventId */
    private $id;

    /** @var string */
    private $displayName;

    /** @var int */
    private $unitId;

    /** @var string */
    private $unitName;

    /** @var string */
    private $state;

    /** @var Date */
    private $startDate;

    /** @var Date */
    private $endDate;

    /** @var int|null */
    private $totalDays;

    /** @var string */
    private $location;

    /** @var string */
    private $registrationNumber;

    /** @var string */
    private $note;

    /** @var int */
    private $scopeId;

    /** @var int */
    private $typeId;

    public function __construct(
        SkautisEventId $id,
        string $displayName,
        int $unitId,
        string $unitName,
        string $state,
        Date $startDate,
        Date $endDate,
        ?int $totalDays,
        ?string $location,
        string $registrationNumber,
        ?string $note,
        int $scopeId,
        int $typeId
    ) {
        $this->id                 = $id;
        $this->displayName        = $displayName;
        $this->unitId             = $unitId;
        $this->unitName           = $unitName;
        $this->state              = $state;
        $this->startDate          = $startDate;
        $this->endDate            = $endDate;
        $this->totalDays          = $totalDays;
        $this->location           = $location ?? '';
        $this->registrationNumber = $registrationNumber;
        $this->note               = $note ?? '';
        $this->scopeId            = $scopeId;
        $this->typeId             = $typeId;
    }

    public function update(
        string $displayName,
        ?string $location,
        Date $startDate,
        Date $endDate,
        int $scopeId,
        int $typeId
    ) : void {
        $this->displayName = $displayName;
        $this->location    = $location;
        $this->startDate   = $startDate;
        $this->endDate     = $endDate;
        $this->scopeId     = $scopeId;
        $this->typeId      = $typeId;
    }

    public function getId() : SkautisEventId
    {
        return $this->id;
    }

    public function getDisplayName() : string
    {
        return $this->displayName;
    }
    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUnitName() : string
    {
        return $this->unitName;
    }

    public function isOpen() : bool
    {
        return $this->state === 'draft';
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

    public function getTotalDays() : ?int
    {
        return $this->totalDays;
    }

    public function getLocation() : string
    {
        return $this->location;
    }

    public function getRegistrationNumber() : string
    {
        return $this->registrationNumber;
    }

    public function getNote() : string
    {
        return $this->note;
    }

    public function getScopeId() : int
    {
        return $this->scopeId;
    }

    public function getTypeId() : int
    {
        return $this->typeId;
    }
}
