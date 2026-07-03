<?php

declare(strict_types=1);

namespace App\Model\Travel\Handlers\Vehicle;

use App\Model\Common\IScanStorage;
use App\Model\Travel\Commands\Vehicle\RemoveRoadworthyScan;
use App\Model\Travel\Repositories\IVehicleRepository;

final class RemoveRoadworthyScanHandler
{
    public function __construct(private IVehicleRepository $vehicles, private IScanStorage $scans)
    {
    }

    public function __invoke(RemoveRoadworthyScan $command): void
    {
        $vehicle = $this->vehicles->find($command->getVehicleId());

        $vehicle->removeRoadworthyScan($command->getPath());

        $this->vehicles->save($vehicle);

        $this->scans->delete($command->getPath());
    }
}
