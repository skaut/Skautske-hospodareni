<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property-read DateTimeImmutable $date
 * @property-read string $transportType
 * @property-read string $startPlace
 * @property-read string $endPlace
 */
class TravelDetails
{
    use SmartObject;

    /** @var DateTimeImmutable */
    private $date;

    /** @var string */
    private $transportType;

    /** @var string */
    private $startPlace;

    /** @var string */
    private $endPlace;

    public function __construct(DateTimeImmutable $date, string $transportType, string $startPlace, string $endPlace)
    {
        $this->date          = $date;
        $this->transportType = $transportType;
        $this->startPlace    = $startPlace;
        $this->endPlace      = $endPlace;
    }

    public function getDate() : DateTimeImmutable
    {
        return $this->date;
    }

    public function getTransportType() : string
    {
        return $this->transportType;
    }

    public function getStartPlace() : string
    {
        return $this->startPlace;
    }

    public function getEndPlace() : string
    {
        return $this->endPlace;
    }
}
