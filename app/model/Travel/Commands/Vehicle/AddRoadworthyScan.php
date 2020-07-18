<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

use Model\Travel\Handlers\Vehicle\AddRoadworthyScanHandler;

/**
 * @see AddRoadworthyScanHandler
 */
final class AddRoadworthyScan
{
    private int $vehicleId;

    private string $fileName;

    private string $scanContents;

    public function __construct(int $vehicleId, string $fileName, string $scanContents)
    {
        $this->vehicleId    = $vehicleId;
        $this->fileName     = $fileName;
        $this->scanContents = $scanContents;
    }

    public function getFileName() : string
    {
        return $this->fileName;
    }

    public function getVehicleId() : int
    {
        return $this->vehicleId;
    }

    public function getScanContents() : string
    {
        return $this->scanContents;
    }
}
