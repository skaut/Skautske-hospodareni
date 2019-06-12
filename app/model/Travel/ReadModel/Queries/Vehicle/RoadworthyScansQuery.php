<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\Queries\Vehicle;

use Model\Travel\ReadModel\QueryHandlers\Vehicle\RoadworthyScansQueryHandler;

/**
 * @see RoadworthyScansQueryHandler
 */
final class RoadworthyScansQuery
{
    /** @var int */
    private $vehicleId;

    public function __construct(int $vehicleId)
    {
        $this->vehicleId = $vehicleId;
    }

    public function getVehicleId() : int
    {
        return $this->vehicleId;
    }
}
