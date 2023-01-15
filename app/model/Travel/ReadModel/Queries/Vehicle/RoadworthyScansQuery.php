<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\Queries\Vehicle;

use Model\Travel\ReadModel\QueryHandlers\Vehicle\RoadworthyScansQueryHandler;

/** @see RoadworthyScansQueryHandler */
final class RoadworthyScansQuery
{
    public function __construct(private int $vehicleId)
    {
    }

    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }
}
