<?php

namespace Model\Travel;

use Mockery as m;
use Model\Travel\Command\TravelDetails;
use Model\Utils\MoneyFactory;
use Money\Money;

class CommandTest extends \Codeception\Test\Unit
{

    public function testCreate(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getId")->andReturn(6);
        $driver = new Passenger("Frantisek Masa", "---", "Brno");
        $purpose = "Cesta na střediskovku";
        $command = new Command(2, $vehicle, $driver, $purpose, "Brno", "", Money::CZK(3120), Money::CZK(500), "");

        $this->assertSame(2, $command->getUnitId());
        $this->assertSame(6, $command->getVehicleId());
        $this->assertSame($driver, $command->getPassenger());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame("Brno", $command->getPlace());
        $this->assertSame("", $command->getFellowPassengers());
        $this->assertEquals(Money::CZK(3120), $command->getFuelPrice());
        $this->assertEquals(Money::CZK(500), $command->getAmortization());
        $this->assertSame("", $command->getNote());
    }

    public function testCalculateTotal(): void
    {
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive("getConsumption")->andReturn(6);

        $command = $this->createCommand($vehicle);

        $date = new \DateTimeImmutable();
        $command->addVehicleTravel(200, new TravelDetails($date,"vau", "Brno", "Praha"));
        $command->addVehicleTravel(220, new TravelDetails($date, "vau", "Praha", "Brno"));
        $command->addTransportTravel(Money::CZK(50000), new TravelDetails($date, "bus", "Brno", "Praha"));

        $expectedPricePerKm = 6 / 100 * 31.20 + 5;
        $this->assertEquals(MoneyFactory::fromFloat(31.20 * 6 / 100), $command->getFuelPricePerKm());
        $this->assertEquals(MoneyFactory::fromFloat($expectedPricePerKm), $command->getPricePerKm());
        $this->assertEquals(MoneyFactory::fromFloat($expectedPricePerKm * 420)->add(Money::CZK(50000)), $command->calculateTotal());
    }

    public function testGetFirstTravelDate(): void
    {
        $command = $this->createCommand();

        $date = new \DateTimeImmutable();

        $command->addVehicleTravel(200, new TravelDetails($date->modify("+ 1 day"), "vau", "Brno", "Praha"));
        $command->addVehicleTravel(220, new TravelDetails($date, "vau", "Praha", "Brno"));
        $command->addTransportTravel(Money::CZK(50000), new TravelDetails($date->modify("+ 3 days"), "bus", "Brno", "Praha"));

        $this->assertSame($date, $command->getFirstTravelDate());
    }

    public function testUpdateMethod(): void
    {
        $command = $this->createCommand();
        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('getId')->andReturn(5);
        $driver = new Passenger("Stig", "000000000", "Neznámá");
        $purpose = "Akce";
        $place = "Praha";
        $fuelPrice = Money::CZK(3000);
        $passengers = "Frantisek Masa";
        $amortizationPerKm = Money::CZK(300);
        $note = "Nothing";

        $command->update($vehicle, $driver, $purpose, $place, $passengers, $fuelPrice, $amortizationPerKm, $note);

        $this->assertSame(5, $command->getVehicleId());
        $this->assertSame($driver, $command->getPassenger());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame($place, $command->getPlace());
        $this->assertSame($passengers, $command->getFellowPassengers());
        $this->assertEquals($fuelPrice, $command->getFuelPrice());
        $this->assertEquals($amortizationPerKm, $command->getAmortization());
        $this->assertSame($note, $command->getNote());
    }

    public function testUpdateVehicleTravel(): void
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(200, $this->getDetails());

        $distance = (float)220;
        $details = new TravelDetails(new \DateTimeImmutable(), "mov", "Praha", "Brno");

        $command->updateVehicleTravel(0, $distance, $details);

        /* @var $travel \Model\Travel\Command\VehicleTravel */
        $travel = $command->getTravels()[0];

        $this->assertSame($distance, $travel->getDistance());
        $this->assertSame($details, $travel->getDetails());
    }

    public function testUpdateTransportTravel(): void
    {
        $command = $this->createCommand();
        $command->addTransportTravel(MoneyFactory::fromFloat(200), $this->getDetails());

        $price = MoneyFactory::fromFloat(320);
        $details = new TravelDetails(new \DateTimeImmutable(), "mov", "Praha", "Brno");

        $command->updateTransportTravel(0, $price, $details);

        /* @var $travel \Model\Travel\Command\TransportTravel */
        $travel = $command->getTravels()[0];

        $this->assertSame($price, $travel->getPrice());
        $this->assertSame($details, $travel->getDetails());
    }

    public function testUpdateNonexistentVehicleTravelThrowsException(): void
    {
        $command = $this->createCommand();

        $this->expectException(TravelNotFoundException::class);

        $command->updateVehicleTravel(20, 200, $this->getDetails());
    }

    public function testUpdateNonexistentTransportTravelThrowsException(): void
    {
        $command = $this->createCommand();

        $this->expectException(TravelNotFoundException::class);

        $command->updateTransportTravel(20, MoneyFactory::fromFloat(200), $this->getDetails());
    }

    public function testUpdateVehicleTravelToTransportTravel()
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(200, $this->getDetails());

        $price = MoneyFactory::fromFloat(200);
        $details = new TravelDetails(new \DateTimeImmutable(), "mov", "Praha", "Brno");

        $command->updateTransportTravel(0, $price, $details);

        /* @var $travel \Model\Travel\Command\TransportTravel */
        $travel = $command->getTravels()[0];

        $this->assertSame($price, $travel->getPrice());
        $this->assertSame($details, $travel->getDetails());
        $this->assertSame(1, $command->getTravelCount());
    }

    public function testUpdateTransportTravelToVehicleTravel()
    {
        $command = $this->createCommand();
        $command->addTransportTravel(MoneyFactory::fromFloat(200), $this->getDetails());

        $distance = 20;
        $details = new TravelDetails(new \DateTimeImmutable(), "mov", "Praha", "Brno");

        $command->updateVehicleTravel(0, $distance, $details);

        /* @var $travel \Model\Travel\Command\VehicleTravel */
        $travel = $command->getTravels()[0];

        $this->assertSame((float)$distance, $travel->getDistance());
        $this->assertSame($details, $travel->getDetails());
        $this->assertSame(1, $command->getTravelCount());
    }

    public function testRemoveVehicleTravel()
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(206, new TravelDetails(new \DateTimeImmutable(), "auv", "Brno", "Praha"));
        $command->addVehicleTravel(206, new TravelDetails(new \DateTimeImmutable(), "auv", "Brno", "Praha"));
        $command->removeTravel(0);
        $this->assertSame(1, $command->getTravelCount());
        $command->removeTravel(1);
        $this->assertSame(0, $command->getTravelCount());
    }

    public function TestGetUsedTransportTypes(): void
    {
        $command = $this->createCommand();
        $date = new \DateTimeImmutable();

        $command->addVehicleTravel(200, new TravelDetails($date, "mov", "Brno", "Praha"));
        $command->addVehicleTravel(200, new TravelDetails($date, "auv", "Brno", "Praha"));
        $command->addTransportTravel(MoneyFactory::fromFloat(200), new TravelDetails($date, "a", "Brno", "Praha"));

        $this->assertEquals(["mov", "auv", "a"], $command->getUsedTransportTypes());
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
                new Passenger("Frantisek Masa", "777777777", "Brno"),
                "Cesta na střediskovku",
                "Brno",
                "",
                Money::CZK(3120),
                Money::CZK(500),
                ""
        );
    }

    private function getDetails(): TravelDetails
    {
        return new TravelDetails(new \DateTimeImmutable(), "auv", "Brno", "Praha");
    }

}
