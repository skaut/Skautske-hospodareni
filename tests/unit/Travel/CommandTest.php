<?php

namespace Model\Travel;

use Mockery as m;

class CommandTest extends \Codeception\Test\Unit
{

    public function testCreate(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getId")->andReturn(6);
        $driver = new Driver("Frantisek Masa", "---");
        $place = "Cesta na střediskovku";
        $command = new Command(2, $vehicle, $driver, $place, "Brno", "", 31.20, 5, "");

        $this->assertSame(2, $command->getUnitId());
        $this->assertSame(6, $command->getVehicleId());
        $this->assertSame($driver, $command->getDriver());
        $this->assertSame($place, $command->getPlace());
        $this->assertSame("", $command->getPassengers());
        $this->assertSame(31.20, $command->getFuelPrice());
        $this->assertSame(5, $command->getAmortization());
        $this->assertSame("", $command->getNote());
    }

    public function testCalculateTotal(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getConsumption")->andReturn(6);
        $driver = new Driver("Frantisek Masa", "---");
        $command = new Command(2, $vehicle, $driver, "Cesta na střediskovku", "Brno", "", 31.20, 5, "");

        $date = new \DateTimeImmutable();
        $command->createTravel($date, 200, new TransportType("vau", TRUE), "Brno", "Praha");
        $command->createTravel($date, 220, new TransportType("vau", TRUE), "Praha", "Brno");
        $command->createTravel($date, 500, new TransportType("bus", FALSE), "Brno", "Praha");

        $this->assertSame(
            (200 + 220) * (6 * 31.20 / 100) + 500,
            $command->calculateTotal()
        );
    }

    private function mockVehicle(): m\MockInterface
    {
        return m::mock(Vehicle::class);
    }

}
