<?php

declare(strict_types=1);

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

    public function update(float $distance, TravelDetails $details) : void
    {
        if ($distance <= 0) {
            throw new \InvalidArgumentException('Distance must be positive number');
        }
        $this->distance = $distance;
        $this->setDetails($details);
    }

    public function getDistance() : float
    {
        return $this->distance;
    }
}
