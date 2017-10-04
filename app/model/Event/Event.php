<?php

namespace Model\Event;

use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $displayName
 * @property-read int $unitId
 * @property-read string $unitName
 * @property-read string $state
 * @property-read \DateTimeImmutable $startDate
 * @property-read \DateTimeImmutable $endDate
 * @property-read int $totalDays
 * @property-read string $location
 * @property-read string $registrationNumber
 */
class Event
{

    use SmartObject;

    /** @var int */
    private $id;

    /** @var string */
    private $displayName;

    /** @var int */
    private $unitId;

    /** @var string */
    private $unitName;

    /** @var string */
    private $state;

    /** @var \DateTimeImmutable */
    private $startDate;

    /** @var \DateTimeImmutable */
    private $endDate;

    /** @var int */
    private $totalDays;

    /** @var string */
    private $location;

    /** @var string */
    private $registrationNumber;

    public function __construct(
        int $id,
        string $displayName,
        int $unitId,
        string $unitName,
        string $state,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        int $totalDays,
        string $location,
        string $registrationNumber
    )
    {
        $this->id = $id;
        $this->displayName = $displayName;
        $this->unitId = $unitId;
        $this->unitName = $unitName;
        $this->state = $state;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->totalDays = $totalDays;
        $this->location = $location;
        $this->registrationNumber = $registrationNumber;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUnitName(): string
    {
        return $this->unitName;
    }

    public function isOpen(): string
    {
        return $this->state === "draft";
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getTotalDays(): int
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

}
