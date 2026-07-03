<?php

declare(strict_types=1);

namespace App\Model\Travel\Commands\Vehicle;

use App\Model\Travel\Handlers\Vehicle\AddRoadworthyScanHandler;

/** @see AddRoadworthyScanHandler */
final class AddRoadworthyScan
{
    public function __construct(private int $vehicleId, private string $fileName, private string $scanContents)
    {
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }

    public function getScanContents(): string
    {
        return $this->scanContents;
    }
}
