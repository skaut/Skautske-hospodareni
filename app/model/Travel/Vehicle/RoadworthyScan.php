<?php

declare(strict_types=1);

namespace Model\Travel\Vehicle;

use Doctrine\ORM\Mapping as ORM;
use Model\Travel\Vehicle;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_vehicle_roadworthy_scan")
 */
class RoadworthyScan
{
    /**
     * @internal only for infrastructure
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     *
     * @var      int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="roadworthyScans")
     *
     * @var Vehicle
     */
    private $vehicle;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $filePath;

    public function __construct(Vehicle $vehicle, string $filePath)
    {
        $this->vehicle  = $vehicle;
        $this->filePath = $filePath;
    }

    public function getFilePath() : string
    {
        return $this->filePath;
    }
}
