<?php

declare(strict_types=1);

namespace Model\Travel\Vehicle;

use Doctrine\ORM\Mapping as ORM;
use Model\Common\FilePath;
use Model\Travel\Vehicle;

/**
 * @ORM\Entity()
 * @ORM\Table(name="tc_vehicle_roadworthy_scan")
 */
class RoadworthyScan
{
    public const FILE_PATH_PREFIX = 'roadworthies';

    /**
     * @internal only for infrastructure
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="roadworthyScans")
     */
    private Vehicle $vehicle;

    /**
     * @ORM\Column(type="file_path")
     */
    private FilePath $filePath;

    public function __construct(Vehicle $vehicle, FilePath $filePath)
    {
        $this->vehicle  = $vehicle;
        $this->filePath = $filePath;
    }

    public function getFilePath() : FilePath
    {
        return $this->filePath;
    }
}
