<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

use Model\Common\FilePath;

final class RemoveRoadworthyScan
{
    /** @var int */
    private $vehicleId;

    /** @var FilePath */
    private $path;

    public function __construct(int $vehicleId, FilePath $path)
    {
        $this->vehicleId = $vehicleId;
        $this->path      = $path;
    }

    public function getVehicleId() : int
    {
        return $this->vehicleId;
    }

    public function getPath() : FilePath
    {
        return $this->path;
    }
}
