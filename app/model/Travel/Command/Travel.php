<?php

namespace Model\Travel\Command;

use DateTimeImmutable;
use Model\Travel\Command;
use Model\Travel\TransportType;
use Model\Travel\WrongVehicleTypeException;

class Travel
{

    /** @var int */
    private $id;

    /** @var DateTimeImmutable */
    private $date;

    /** @var float */
    private $distance;

    /** @var TransportType */
    private $transportType;

    /** @var string */
    private $startPlace;

    /** @var string */
    private $endPlace;

    /** @var Command @internal */
    private $command;

    public function __construct(
        DateTimeImmutable $date,
        float $distance,
        TransportType $transportType,
        string $startPlace,
        string $endPlace,
        Command $command
    )
    {
        if($this->distance <= 0) {
            throw new \InvalidArgumentException("Distance can't be negative");
        }

        $this->date = $date;
        $this->distance = $distance;
        $this->transportType = $transportType;
        $this->startPlace = $startPlace;
        $this->endPlace = $endPlace;
        $this->command = $command;
    }

    public function getAmount(): float
    {
        if($this->transportType->hasFuel()) {
            throw new WrongVehicleTypeException("Can't get amount from travel with fueled vehicle");
        }

        return $this->distance;
    }

    public function getDistance(): float
    {
        if(!$this->transportType->hasFuel()) {
            throw new WrongVehicleTypeException("Can't get distance from travel with non-fueled vehicle");
        }

        return $this->distance;
    }

    public function getTransportType(): TransportType
    {
        return $this->transportType;
    }

}
