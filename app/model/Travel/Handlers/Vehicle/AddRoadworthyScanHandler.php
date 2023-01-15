<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Vehicle;

use Model\Common\FilePath;
use Model\Common\IScanStorage;
use Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use Model\Travel\Repositories\IVehicleRepository;
use Model\Travel\Vehicle\RoadworthyScan;

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
