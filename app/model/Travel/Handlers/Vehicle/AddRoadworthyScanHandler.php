<?php

declare(strict_types=1);

namespace Model\Travel\Handlers\Vehicle;

use Cake\Chronos\Date;
use Model\Common\IScanStorage;
use Model\Travel\Commands\Vehicle\AddRoadworthyScan;
use Model\Travel\Repositories\IVehicleRepository;
use Ramsey\Uuid\Uuid;

final class AddRoadworthyScanHandler
{
    private const PREFIX = 'roadworthies/';

    /** @var IVehicleRepository */
    private $vehicles;

    /** @var IScanStorage */
    private $scanStorage;

    public function __construct(IVehicleRepository $vehicles, IScanStorage $scanStorage)
    {
        $this->vehicles    = $vehicles;
        $this->scanStorage = $scanStorage;
    }

    public function __invoke(AddRoadworthyScan $command) : void
    {
        $vehicle = $this->vehicles->find($command->getVehicleId());

        $path = $this->generatePath($command->getFileName());

        $this->scanStorage->save($path, $command->getScanContents());

        $vehicle->addRoadworthyScan($path);

        $this->vehicles->save($vehicle);
    }

    private function generatePath(string $originalFileName) : string
    {
        $date = Date::today();

        return self::PREFIX . $date->format('Y/m') . '/' . Uuid::uuid4()->toString() . '/' . $originalFileName;
    }
}
