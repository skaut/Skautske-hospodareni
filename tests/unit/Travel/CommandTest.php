<?php

namespace Model\Travel;

use Mockery as m;

class CommandTest extends \Codeception\Test\Unit
{

    public function testCalculateTotal(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getConsumption")->andReturn(6);

        $command = new Command(2, $vehicle, NULL, "Cesta na střediskovku", "Brno", "", 31.20, 5, "");

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
