<?php

namespace Model\Travel;

use Mockery as m;
use Model\Travel\Command\TransportType;

class CommandTest extends \Codeception\Test\Unit
{

    public function testCreate(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getId")->andReturn(6);
        $driver = new Driver("Frantisek Masa", "---", "Brno");
        $purpose = "Cesta na střediskovku";
        $command = new Command(2, $vehicle, $driver, $purpose, "Brno", "", 31.20, 5, "");

        $this->assertSame(2, $command->getUnitId());
        $this->assertSame(6, $command->getVehicleId());
        $this->assertSame($driver, $command->getDriver());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame("Brno", $command->getPlace());
        $this->assertSame("", $command->getPassengers());
        $this->assertSame(31.20, $command->getFuelPrice());
        $this->assertSame(5.0, $command->getAmortization());
        $this->assertSame("", $command->getNote());
    }

    public function testCalculateTotal(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getConsumption")->andReturn(6);

        $command = $this->createCommand($vehicle);

        $date = new \DateTimeImmutable();
        $command->createTravel($date, 200, new TransportType("vau", TRUE), "Brno", "Praha");
        $command->createTravel($date, 220, new TransportType("vau", TRUE), "Praha", "Brno");
        $command->createTravel($date, 500, new TransportType("bus", FALSE), "Brno", "Praha");

        $this->assertSame(
            (200 + 220) * (6 * 31.20 / 100) + 500,
            $command->calculateTotal()
        );
    }

    public function testGetFirstTravelDate(): void
    {
        $command = $this->createCommand();

        $date = new \DateTimeImmutable();

        $command->createTravel($date->modify("+ 1 day"), 200, new TransportType("vau", TRUE), "Brno", "Praha");
        $command->createTravel($date, 220, new TransportType("vau", TRUE), "Praha", "Brno");
        $command->createTravel($date->modify("+ 3 days"), 500, new TransportType("bus", FALSE), "Brno", "Praha");

        $this->assertSame($date, $command->getFirstTravelDate());
    }

    public function testUpdateMethod(): void
    {
        $command = $this->createCommand();
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('getId')->andReturn(5);
        $driver = new Driver("Stig", "000000000", "Neznámá");
        $purpose = "Akce";
        $place = "Praha";
        $fuelPrice = 30.0;
        $passengers = "Frantisek Masa";
        $amortizationPerKm = 3.0;
        $note = "Nothing";

        $command->update($vehicle, $driver, $purpose, $place, $passengers, $fuelPrice, $amortizationPerKm, $note);

        $this->assertSame(5, $command->getVehicleId());
        $this->assertSame($driver, $command->getDriver());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame($place, $command->getPlace());
        $this->assertSame($passengers, $command->getPassengers());
        $this->assertSame($fuelPrice, $command->getFuelPrice());
        $this->assertSame($amortizationPerKm, $command->getAmortization());
        $this->assertSame($note, $command->getNote());
    }

    private function mockVehicle(): m\MockInterface
    {
        return m::mock(Vehicle::class);
    }

    private function createCommand(?Vehicle $vehicle = NULL): Command
    {
        return new Command(
            10,
            $vehicle ?? $this->mockVehicle(),
                new Driver("Frantisek Masa", "777777777", "Brno"),
                "Cesta na střediskovku",
                "Brno",
                "",
                31.20,
                5,
                ""
        );
    }

}
