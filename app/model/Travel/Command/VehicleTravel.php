<?php

declare(strict_types=1);

namespace Model\Travel\Command;

use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Command;
use function sprintf;

/**
 * @ORM\Entity()
 */
class VehicleTravel extends Travel
{
    /**
     * @var float
     * @ORM\Column(type="decimal", precision=9, scale=2, options={"unsigned"=true})
     */
    private $distance;

    public function __construct(int $id, float $distance, TravelDetails $details, Command $command)
    {
        parent::__construct($id, $command, $details);
        $this->setDistance($distance);
    }

    public function update(float $distance, TravelDetails $details) : void
    {
        $this->setDistance($distance);
        $this->setDetails($details);
    }

    public function getDistance() : float
    {
        return $this->distance;
    }

    private function setDistance(float $distance) : void
    {
        if ($distance <= 0) {
            throw new \InvalidArgumentException(sprintf('Distance must be positive number, %f given.', $distance));
        }

        $this->distance = $distance;
    }
}
