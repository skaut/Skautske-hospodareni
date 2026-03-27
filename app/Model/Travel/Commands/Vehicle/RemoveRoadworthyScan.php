<?php

declare(strict_types=1);

namespace App\Model\Travel\Commands\Vehicle;

use App\Model\Common\FilePath;
use App\Model\Travel\Handlers\Vehicle\RemoveRoadworthyScanHandler;

/** @see RemoveRoadworthyScanHandler */
final class RemoveRoadworthyScan
{
    public function __construct(private int $vehicleId, private FilePath $path)
    {
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getPath(): FilePath
    {
        return $this->path;
    }
}
