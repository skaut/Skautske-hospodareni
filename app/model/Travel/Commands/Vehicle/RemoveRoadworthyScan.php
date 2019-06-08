<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

final class RemoveRoadworthyScan
{
    /** @var int */
    private $vehicleId;

    /** @var string */
    private $path;

    public function __construct(int $vehicleId, string $path)
    {
        $this->vehicleId = $vehicleId;
        $this->path      = $path;
    }

    public function getVehicleId() : int
    {
        return $this->vehicleId;
    }

    public function getPath() : string
    {
        return $this->path;
    }
}
