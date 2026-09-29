<?php

declare(strict_types=1);

namespace App\Model\Travel;

use App\Model\Common\Services\QueryBus;
use App\Model\Travel\Repositories\ICommandRepository;
use App\Model\Travel\Repositories\IContractRepository;
use App\Model\Travel\Repositories\IVehicleRepository;
use App\Model\Travel\Vehicle\Metadata;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use Codeception\Test\Unit as UnitTest;
use DateTimeImmutable;
use Mockery as m;
use Money\Money;

class TravelServiceTest extends UnitTest
{
    private const UNIT_ID = 10;
    private const VEHICLE_ID = 5;

    public function testArchivedVehicleCannotBeUsedForNewCommand(): void
    {
        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($this->createVehicle(true));

        $commands = m::mock(ICommandRepository::class);
        $commands->shouldNotReceive('save');

        $this->expectException(VehicleIsArchived::class);

        $this->createService($vehicles, $commands)->addCommand(
            self::UNIT_ID,
            null,
            new Passenger('Frantisek Masa', '---', 'Brno'),
            self::VEHICLE_ID,
            'Cesta na středisko',
            'Brno',
            '',
            Money::CZK(3120),
            Money::CZK(500),
            '',
            [],
            1,
            '',
        );
    }

    public function testActiveVehicleCanBeUsedForNewCommand(): void
    {
        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($this->createVehicle(false));

        $commands = m::mock(ICommandRepository::class);
        $commands->shouldReceive('save')->once();

        $this->createService($vehicles, $commands)->addCommand(
            self::UNIT_ID,
            null,
            new Passenger('Frantisek Masa', '---', 'Brno'),
            self::VEHICLE_ID,
            'Cesta na středisko',
            'Brno',
            '',
            Money::CZK(3120),
            Money::CZK(500),
            '',
            [],
            1,
            '',
        );
    }

    /**
     * A vehicle archived after the command was created still belongs to that command,
     * so editing the command must not fail.
     */
    public function testCommandKeepsItsVehicleAfterArchivation(): void
    {
        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($this->createVehicle(true));

        $command = m::mock(Command::class);
        $command->shouldReceive('getVehicleId')->andReturn(self::VEHICLE_ID);
        $command->shouldReceive('getUsedTransportTypes')->andReturn([]);
        $command->shouldReceive('update')->once();

        $commands = m::mock(ICommandRepository::class);
        $commands->shouldReceive('find')->with(1)->andReturn($command);
        $commands->shouldReceive('save')->once();

        $this->createService($vehicles, $commands)->updateCommand(
            1,
            null,
            new Passenger('Frantisek Masa', '---', 'Brno'),
            self::VEHICLE_ID,
            'Cesta na středisko',
            'Brno',
            '',
            Money::CZK(3120),
            Money::CZK(500),
            '',
            [],
            '',
        );
    }

    public function testCommandCannotBeSwitchedToDifferentArchivedVehicle(): void
    {
        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($this->createVehicle(true));

        $command = m::mock(Command::class);
        $command->shouldReceive('getVehicleId')->andReturn(self::VEHICLE_ID + 1);
        $command->shouldNotReceive('update');

        $commands = m::mock(ICommandRepository::class);
        $commands->shouldReceive('find')->with(1)->andReturn($command);
        $commands->shouldNotReceive('save');

        $this->expectException(VehicleIsArchived::class);

        $this->createService($vehicles, $commands)->updateCommand(
            1,
            null,
            new Passenger('Frantisek Masa', '---', 'Brno'),
            self::VEHICLE_ID,
            'Cesta na středisko',
            'Brno',
            '',
            Money::CZK(3120),
            Money::CZK(500),
            '',
            [],
            '',
        );
    }

    public function testRestoreVehicleReturnsItAmongActiveOnes(): void
    {
        $vehicle = $this->createVehicle(true);

        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($vehicle);
        $vehicles->shouldReceive('save')->once()->with($vehicle);

        $this->createService($vehicles, m::mock(ICommandRepository::class))->restoreVehicle(self::VEHICLE_ID);

        $this->assertFalse($vehicle->isArchived());
    }

    public function testRestoreActiveVehicleDoesNothing(): void
    {
        $vehicle = $this->createVehicle(false);

        $vehicles = m::mock(IVehicleRepository::class);
        $vehicles->shouldReceive('find')->with(self::VEHICLE_ID)->andReturn($vehicle);
        $vehicles->shouldNotReceive('save');

        $this->createService($vehicles, m::mock(ICommandRepository::class))->restoreVehicle(self::VEHICLE_ID);

        $this->assertFalse($vehicle->isArchived());
    }

    private function createVehicle(bool $archived): Vehicle
    {
        $unit = m::mock(Unit::class, ['getId' => self::UNIT_ID]);
        $vehicle = new Vehicle('Osobní', $unit, null, '1A2 3456', 6.0, new Metadata(new DateTimeImmutable(), 'FM'));

        if ($archived) {
            $vehicle->archive();
        }

        return $vehicle;
    }

    private function createService(IVehicleRepository $vehicles, ICommandRepository $commands): TravelService
    {
        return new TravelService(
            $vehicles,
            $commands,
            m::mock(IContractRepository::class),
            m::mock(IUnitRepository::class),
            m::mock(QueryBus::class),
        );
    }
}
