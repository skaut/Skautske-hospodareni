<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

use Model\Common\FilePath;

final class RemoveRoadworthyScan
{
    private int $vehicleId;

    private FilePath $path;

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
