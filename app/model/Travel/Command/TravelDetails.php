<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;

/**
 * @ORM\Embeddable()
 * @property-read DateTimeImmutable $date
 * @property-read string $transportType
 * @property-read string $startPlace
 * @property-read string $endPlace
 */
class TravelDetails
{
    use SmartObject;

    /**
     * @var DateTimeImmutable
     * @ORM\Column(type="datetime_immutable", name="start_date")
     */
    private $date;

    /**
     * @var string
     * @ORM\Column(type="string", name="type", length=5)
     */
    private $transportType;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
    private $startPlace;

    /**
     * @var string
     * @ORM\Column(type="string", length=64)
     */
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
