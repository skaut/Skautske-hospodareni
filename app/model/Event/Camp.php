<?php

declare(strict_types=1);

namespace Model\Event;

use Cake\Chronos\Date;
use Nette\SmartObject;

/**
 * @property-read SkautisCampId $id
 * @property-read string $displayName
 * @property-read int $unitId
 * @property-read string $unitName
 * @property-read Date $startDate
 * @property-read Date $endDate
 * @property-read string $location
 * @property-read string $state
 * @property-read string $registrationNumber
 */
class Camp
{
    use SmartObject;

    /** @var SkautisCampId */
    private $id;

    /** @var string */
    private $displayName;

    /** @var int */
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

    public function __construct(
        SkautisCampId $id,
        string $displayName,
        int $unitId,
        string $unitName,
        Date $startDate,
        Date $endDate,
        string $location,
        string $state,
        string $registrationNumber
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
    }

    public function getId() : SkautisCampId
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
}
