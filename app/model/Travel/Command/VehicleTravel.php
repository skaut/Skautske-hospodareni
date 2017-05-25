<?php

namespace Model\Travel\Command;

use Model\Travel\Command;

class VehicleTravel extends Travel
{

    /** @var float */
    private $distance;

    public function __construct(float $distance, TravelDetails $details, Command $command)
    {
        parent::__construct($command, $details);
        $this->distance = $distance;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

}
