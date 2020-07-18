<?php

declare(strict_types=1);

namespace Model\Travel\ReadModel\QueryHandlers\Vehicle;

use Model\Common\File;
use Model\Common\IScanStorage;
use Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\Vehicle\RoadworthyScan;
use function array_map;

final class RoadworthyScansQueryHandler
{
    private IVehicleRepository $vehicles;

    private IScanStorage $scans;

    public function __construct(IVehicleRepository $vehicles, IScanStorage $scans)
    {
        $this->vehicles = $vehicles;
        $this->scans    = $scans;
    }

    /**
     * @return File[]
     */
    public function __invoke(RoadworthyScansQuery $query) : array
    {
        $vehicle = $this->vehicles->find($query->getVehicleId());

        return array_map(
            function (RoadworthyScan $scan) : File {
                return $this->scans->get($scan->getFilePath());
            },
            $vehicle->getRoadworthyScans()
        );
    }
}
