<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Vehicle;

use Model\Common\IScanStorage;
use Model\Travel\Commands\Vehicle\RemoveRoadworthyScan;
use Model\Travel\Repositories\IVehicleRepository;

final class RemoveRoadworthyScanHandler
{
    /** @var IVehicleRepository */
    private $vehicles;

    /** @var IScanStorage */
    private $scans;

    public function __construct(IVehicleRepository $vehicles, IScanStorage $scans)
    {
        $this->vehicles = $vehicles;
        $this->scans    = $scans;
    }

    public function __invoke(RemoveRoadworthyScan $command) : void
    {
        $vehicle = $this->vehicles->find($command->getVehicleId());

        $vehicle->removeRoadworthyScan($command->getPath());

        $this->vehicles->save($vehicle);

        $this->scans->delete($command->getPath());
    }
}
