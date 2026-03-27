<?php

declare(strict_types=1);

namespace App\Model\Travel\Handlers\Vehicle;

use App\Model\Common\FilePath;
use App\Model\Common\IScanStorage;
use App\Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Vehicle\RoadworthyScan;

final class AddRoadworthyScanHandler
{
    public function __construct(private IVehicleRepository $vehicles, private IScanStorage $scanStorage)
    {
    }

    public function __invoke(AddRoadworthyScan $command): void
    {
        $vehicle = $this->vehicles->find($command->getVehicleId());

        $path = FilePath::generate(RoadworthyScan::FILE_PATH_PREFIX, $command->getFileName());

        $this->scanStorage->save($path, $command->getScanContents());

        $vehicle->addRoadworthyScan($path);

        $this->vehicles->save($vehicle);
    }
}
