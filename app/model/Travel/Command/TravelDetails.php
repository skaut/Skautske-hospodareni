<?php

namespace Model\Travel\Command;

class TravelDetails
{

    /** @var \DateTimeImmutable */
    private $date;

    /** @var string */
    private $transportType;

    /** @var string */
    private $startPlace;

    /** @var string */
    private $endPlace;

    public function __construct(\DateTimeImmutable $date, string $transportType, string $startPlace, string $endPlace)
    {
        $this->date = $date;
        $this->transportType = $transportType;
        $this->startPlace = $startPlace;
        $this->endPlace = $endPlace;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getTransportType(): string
    {
        return $this->transportType;
    }

    public function getStartPlace(): string
    {
        return $this->startPlace;
    }

    public function getEndPlace(): string
    {
        return $this->endPlace;
    }

}
