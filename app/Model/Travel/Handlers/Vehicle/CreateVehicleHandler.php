<?php

declare(strict_types=1);

namespace App\Model\Travel\Handlers\Vehicle;

use App\Model\Common\Repositories\IUserRepository;
use App\Model\Travel\Commands\Vehicle\CreateVehicle;
use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Vehicle;
use App\Model\Unit\Repositories\IUnitRepository;
use DateTimeImmutable;

final class CreateVehicleHandler
{
    public function __construct(private IVehicleRepository $vehicles, private IUserRepository $users, private IUnitRepository $units)
    {
    }

    public function __invoke(CreateVehicle $command): void
    {
        $unit = $this->units->find($command->getUnitId());

        $subunit = $command->getSubunitId() !== null
            ? $this->units->find($command->getSubunitId())
            : null;

        $user = $this->users->find($command->getUserId());

        $metadata = new Vehicle\Metadata(new DateTimeImmutable(), $user->getName());

        $vehicle = new Vehicle(
            $command->getType(),
            $unit,
            $subunit,
            $command->getRegistration(),
            $command->getConsumption(),
            $metadata,
        );

        $this->vehicles->save($vehicle);
    }
}
