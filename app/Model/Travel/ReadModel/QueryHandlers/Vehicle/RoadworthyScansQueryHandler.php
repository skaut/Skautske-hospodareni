<?php

declare(strict_types=1);

namespace App\Model\Travel\ReadModel\QueryHandlers\Vehicle;

use App\Model\Common\File;
use App\Model\Common\IScanStorage;
use App\Model\Travel\ReadModel\Queries\Vehicle\RoadworthyScansQuery;
use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Vehicle\RoadworthyScan;

use function array_map;

final class RoadworthyScansQueryHandler
{
    public function __construct(private IVehicleRepository $vehicles, private IScanStorage $scans)
    {
    }

    /** @return File[] */
    public function __invoke(RoadworthyScansQuery $query): array
    {
        $vehicle = $this->vehicles->find($query->getVehicleId());

        return array_map(
            function (RoadworthyScan $scan): File {
                return $this->scans->get($scan->getFilePath());
            },
            $vehicle->getRoadworthyScans(),
        );
    }
}
