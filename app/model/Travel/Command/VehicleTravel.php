<?php

namespace Model\Travel\Command;

use Model\Travel\Command;

class VehicleTravel extends Travel
{

    /** @var float */
    private $distance;

    public function __construct(int $id, float $distance, TravelDetails $details, Command $command)
    {
        parent::__construct($id, $command, $details);
        $this->distance = $distance;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

}
