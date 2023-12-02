<?php

declare(strict_types=1);

namespace Model\Travel;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use Mockery;
use Model\Travel\Command\TransportTravel;
use Model\Travel\Command\TravelDetails;
use Model\Travel\Command\VehicleTravel;
use Model\Travel\Travel\TransportType;
use Model\Utils\MoneyFactory;
use Money\Money;
use Throwable;

use function assert;

class CommandTest extends Unit
{
    public function testCreate(): void
    {
        $vehicle = Mockery::mock(Vehicle::class);
        $vehicle->shouldReceive('getId')->andReturn(6);

        $driver  = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose = 'Cesta na střediskovku';
        $command = new Command(2, $vehicle, $driver, $purpose, 'Brno', '', Money::CZK(3120), Money::CZK(500), '', null, [], '');

        $this->assertSame(2, $command->getUnitId());
        $this->assertSame(6, $command->getVehicleId());
        $this->assertSame($driver, $command->getPassenger());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame('Brno', $command->getPlace());
        $this->assertSame('', $command->getFellowPassengers());
        $this->assertEquals(Money::CZK(3120), $command->getFuelPrice());
        $this->assertEquals(Money::CZK(500), $command->getAmortization());
        $this->assertSame('', $command->getNote());
    }

    public function testCalculateTotal(): void
    {
        $vehicle = Mockery::mock(Vehicle::class);
        $vehicle->shouldReceive('getConsumption')->andReturn(6);

        $command = $this->createCommand($vehicle);

        $date = ChronosDate::now();
        $command->addVehicleTravel(
            200,
            new TravelDetails($date, TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );
        $command->addVehicleTravel(
            220,
            new TravelDetails($date, TransportType::get(TransportType::CAR), 'Praha', 'Brno'),
        );
        $command->addTransportTravel(
            Money::CZK(50000),
            new TravelDetails($date, TransportType::get(TransportType::BUS), 'Brno', 'Praha'),
        );

        $expectedPricePerKm = 6 / 100 * 31.20 + 5;
        $this->assertEquals(MoneyFactory::fromFloat(31.20 * 6 / 100), $command->getFuelPricePerKm());
        $this->assertEquals(MoneyFactory::fromFloat($expectedPricePerKm), $command->getPricePerKm());

        $total = MoneyFactory::fromFloat($expectedPricePerKm * 420)->add(Money::CZK(50000));
        $this->assertEquals(MoneyFactory::floor($total), $command->calculateTotal());
    }

    public function testTotalIsFloored(): void
    {
        $command = new Command(
            123,
            null,
            new Passenger('František Maša', 'Test', 'Test'),
            '-',
            'Brno',
            '',
            MoneyFactory::fromFloat(100),
            MoneyFactory::fromFloat(3),
            '',
            null,
            [],
            '',
        );

        $command->addTransportTravel(
            MoneyFactory::fromFloat(500.6),
            new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );

        $this->assertEquals(MoneyFactory::fromFloat(500), $command->calculateTotal());
    }

    public function testGetFirstTravelDate(): void
    {
        $command = $this->createCommand();

        $date = ChronosDate::now();

        $command->addVehicleTravel(
            200,
            new TravelDetails($date->modify('+ 1 day'), TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );
        $command->addVehicleTravel(
            220,
            new TravelDetails($date, TransportType::get(TransportType::CAR), 'Praha', 'Brno'),
        );
        $command->addTransportTravel(
            Money::CZK(50000),
            new TravelDetails($date->modify('+ 3 days'), TransportType::get(TransportType::BUS), 'Brno', 'Praha'),
        );

        $this->assertSame($date->format('Y-m-d'), $command->getFirstTravelDate()->format('Y-m-d'));
    }

    public function testUpdateMethod(): void
    {
        $command = $this->createCommand();

        $vehicle = Mockery::mock(Vehicle::class);
        $vehicle->shouldReceive('getId')->andReturn(5);

        $driver            = new Passenger('Stig', '000000000', 'Neznámá');
        $purpose           = 'Akce';
        $place             = 'Praha';
        $fuelPrice         = Money::CZK(3000);
        $passengers        = 'Frantisek Masa';
        $amortizationPerKm = Money::CZK(300);
        $note              = 'Nothing';
        $transport_types   = [TransportType::get(TransportType::CAR), TransportType::get(TransportType::MOTORCYCLE)];
        $unit              = '123.45';

        $command->update($vehicle, $driver, $purpose, $place, $passengers, $fuelPrice, $amortizationPerKm, $note, $transport_types, $unit);

        $this->assertSame(5, $command->getVehicleId());
        $this->assertSame($driver, $command->getPassenger());
        $this->assertSame($purpose, $command->getPurpose());
        $this->assertSame($place, $command->getPlace());
        $this->assertSame($passengers, $command->getFellowPassengers());
        $this->assertEquals($fuelPrice, $command->getFuelPrice());
        $this->assertEquals($amortizationPerKm, $command->getAmortization());
        $this->assertSame($note, $command->getNote());
        $this->assertSame($transport_types, $command->getTransportTypes());
        $this->assertSame($unit, $command->getUnit());
    }

    public function testUpdateVehicleTravel(): void
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(200, $this->getDetails());

        $distance = 220.0;
        $details  = new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::MOTORCYCLE), 'Praha', 'Brno');

        $command->updateVehicleTravel(0, $distance, $details);

        $travel = $command->getTravels()[0];
        assert($travel instanceof VehicleTravel);

        $this->assertSame($distance, $travel->getDistance());
        $this->assertSame($details, $travel->getDetails());
    }

    public function testUpdateTransportTravel(): void
    {
        $command = $this->createCommand();
        $command->addTransportTravel(MoneyFactory::fromFloat(200), $this->getDetails());

        $price   = MoneyFactory::fromFloat(320);
        $details = new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::MOTORCYCLE), 'Praha', 'Brno');

        $command->updateTransportTravel(0, $price, $details);

        $travel = $command->getTravels()[0];
        assert($travel instanceof TransportTravel);

        $this->assertSame($price, $travel->getPrice());
        $this->assertSame($details, $travel->getDetails());
    }

    public function testUpdateNonexistentVehicleTravelThrowsException(): void
    {
        $command = $this->createCommand();

        $this->expectException(TravelNotFound::class);

        $command->updateVehicleTravel(20, 200, $this->getDetails());
    }

    public function testUpdateNonexistentTransportTravelThrowsException(): void
    {
        $command = $this->createCommand();

        $this->expectException(TravelNotFound::class);

        $command->updateTransportTravel(20, MoneyFactory::fromFloat(200), $this->getDetails());
    }

    public function testUpdateVehicleTravelToTransportTravel(): void
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(200, $this->getDetails());

        $price   = MoneyFactory::fromFloat(200);
        $details = new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::MOTORCYCLE), 'Praha', 'Brno');

        $command->updateTransportTravel(0, $price, $details);

        $travel = $command->getTravels()[0];
        assert($travel instanceof TransportTravel);

        $this->assertSame($price, $travel->getPrice());
        $this->assertSame($details, $travel->getDetails());
        $this->assertSame(1, $command->getTravelCount());
    }

    public function testUpdateTransportTravelToVehicleTravel(): void
    {
        $command = $this->createCommand();
        $command->addTransportTravel(MoneyFactory::fromFloat(200), $this->getDetails());

        $distance = 20;
        $details  = new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::MOTORCYCLE), 'Praha', 'Brno');

        $command->updateVehicleTravel(0, $distance, $details);

        $travel = $command->getTravels()[0];
        assert($travel instanceof VehicleTravel);

        $this->assertSame((float) $distance, $travel->getDistance());
        $this->assertSame($details, $travel->getDetails());
        $this->assertSame(1, $command->getTravelCount());
    }

    public function testRemoveVehicleTravel(): void
    {
        $command = $this->createCommand();
        $command->addVehicleTravel(
            206,
            new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );
        $command->addVehicleTravel(
            206,
            new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );
        $command->removeTravel(0);
        $this->assertSame(1, $command->getTravelCount());
        $command->removeTravel(1);
        $this->assertSame(0, $command->getTravelCount());
    }

    public function testGetUsedTransportTypes(): void
    {
        $command = $this->createCommand();
        $date    = ChronosDate::now();

        $command->addVehicleTravel(
            200,
            new TravelDetails($date, TransportType::get(TransportType::MOTORCYCLE), 'Brno', 'Praha'),
        );
        $command->addVehicleTravel(
            200,
            new TravelDetails($date, TransportType::get(TransportType::CAR), 'Brno', 'Praha'),
        );
        $command->addTransportTravel(
            MoneyFactory::fromFloat(200),
            new TravelDetails($date, TransportType::get(TransportType::BUS), 'Brno', 'Praha'),
        );

        $this->assertEquals(
            [
                TransportType::get(TransportType::MOTORCYCLE),
                TransportType::get(TransportType::CAR),
                TransportType::get(TransportType::BUS),
            ],
            $command->getUsedTransportTypes(),
        );
    }

    public function testCloseCommand(): void
    {
        $command = $this->createCommand();
        $now     = new DateTimeImmutable();
        $command->close($now);

        $this->assertSame($now, $command->getClosedAt());
    }

    public function testReopenCommand(): void
    {
        $command = $this->createCommand();
        $command->close(new DateTimeImmutable());

        $command->open();

        $this->assertNull($command->getClosedAt());
    }

    public function testClosingClosedCommandDoesntChangeClosedTime(): void
    {
        $command  = $this->createCommand();
        $closedAt = new DateTimeImmutable();
        $command->close($closedAt);

        $command->close($closedAt->add(DateInterval::createFromDateString('1 day')));

        $this->assertSame($closedAt, $command->getClosedAt());
    }

    /** @dataProvider dataNegativeOrZero */
    public function testAddingVehicleTravelWithNegativeOrZeroDistanceThrowsException(float $distance): void
    {
        $command = $this->createCommand(Mockery::mock(Vehicle::class));

        $this->expectException(InvalidArgumentException::class);

        $command->addVehicleTravel($distance, $this->getDetails());
    }

    /** @dataProvider dataNegativeOrZero */
    public function testAddingTransportTravelWithNegativeOrZeroPriceThrowsException(float $price): void
    {
        $command = $this->createCommand();

        $this->expectException(InvalidArgumentException::class);

        $command->addTransportTravel(MoneyFactory::fromFloat($price), $this->getDetails());
    }

    /**
     * @throws Throwable
     *
     * @dataProvider dataNegativeOrZero
     */
    public function testUpdatingVehicleTravelWithNegativeOrZeroDistanceThrowsException(float $distance): void
    {
        $command = $this->createCommand(Mockery::mock(Vehicle::class));
        $command->addVehicleTravel(10, $this->getDetails());

        $this->expectException(InvalidArgumentException::class);

        $command->updateVehicleTravel(0, $distance, $this->getDetails());
    }

    /**
     * @throws Throwable
     *
     * @dataProvider dataNegativeOrZero
     */
    public function testUpdatingTransportTravelWithNegativeOrZeroPriceThrowsException(float $price): void
    {
        $command = $this->createCommand();
        $command->addTransportTravel(MoneyFactory::fromFloat(10), $this->getDetails());

        $this->expectException(InvalidArgumentException::class);

        $command->updateTransportTravel(0, MoneyFactory::fromFloat($price), $this->getDetails());
    }

    /** @return float[][] */
    public function dataNegativeOrZero(): array
    {
        return [
            [0],
            [-0.01],
        ];
    }

    public function testTransportTravelIsDuplicated(): void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(0), Money::CZK(0), '', null, [], '');

        $command->addTransportTravel(
            Money::CZK(100),
            new Command\TravelDetails(
                new ChronosDate('now'),
                TransportType::get(TransportType::BUS),
                'Praha',
                'Brno',
            ),
        );

        $travel = $command->getTravels()[0];

        $command->duplicateTravel($travel->getId());

        $duplicatedTravel = $command->getTravels()[1];

        $travelDetails           = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        self::assertNotEquals($travel->getId(), $duplicatedTravel->getId());
        self::assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        self::assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        self::assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        self::assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }

    public function testVehicleTravelIsDuplicated(): void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(3120), Money::CZK(500), '', null, [], '');

        $command->addVehicleTravel(
            123,
            new Command\TravelDetails(
                new ChronosDate('now'),
                TransportType::get(TransportType::CAR),
                'Praha',
                'Brno',
            ),
        );

        $travel = $command->getTravels()[0];

        $command->duplicateTravel($travel->getId());

        $duplicatedTravel = $command->getTravels()[1];

        $travelDetails           = $travel->getDetails();
        $duplicatedTravelDetails = $duplicatedTravel->getDetails();

        self::assertNotEquals($travel->getId(), $duplicatedTravel->getId());
        self::assertEquals($travelDetails->getDate(), $duplicatedTravelDetails->getDate());
        self::assertEquals($travelDetails->getTransportType(), $duplicatedTravelDetails->getTransportType());
        self::assertEquals($travelDetails->getStartPlace(), $duplicatedTravelDetails->getStartPlace());
        self::assertEquals($travelDetails->getEndPlace(), $duplicatedTravelDetails->getEndPlace());
    }

    public function testTransportTravelIsReturnAdded(): void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(0), Money::CZK(0), '', null, [], '');

        $command->addTransportTravel(
            Money::CZK(100),
            new Command\TravelDetails(
                new ChronosDate('now'),
                TransportType::get(TransportType::BUS),
                'Praha',
                'Brno',
            ),
        );

        $travel = $command->getTravels()[0];

        $command->addReturnTravel($travel->getId());

        $backTravel = $command->getTravels()[1];

        $travelDetails     = $travel->getDetails();
        $backTravelDetails = $backTravel->getDetails();

        self::assertNotEquals($travel->getId(), $backTravel->getId());
        self::assertEquals($travelDetails->getDate(), $backTravelDetails->getDate());
        self::assertEquals($travelDetails->getTransportType(), $backTravelDetails->getTransportType());
        self::assertEquals($travelDetails->getEndPlace(), $backTravelDetails->getStartPlace());
        self::assertEquals($travelDetails->getStartPlace(), $backTravelDetails->getEndPlace());
    }

    public function testVehicleTravelIsReturnAdded(): void
    {
        $passenger = new Passenger('Frantisek Masa', '---', 'Brno');
        $purpose   = 'Cesta na střediskovku';
        $command   = new Command(1, null, $passenger, $purpose, 'Brno', '', Money::CZK(3120), Money::CZK(500), '', null, [], '');

        $command->addVehicleTravel(
            123,
            new Command\TravelDetails(
                new ChronosDate('now'),
                TransportType::get(TransportType::CAR),
                'Praha',
                'Brno',
            ),
        );

        $travel = $command->getTravels()[0];

        $command->addReturnTravel($travel->getId());

        $backTravel = $command->getTravels()[1];

        $travelDetails     = $travel->getDetails();
        $backTravelDetails = $backTravel->getDetails();

        self::assertNotEquals($travel->getId(), $backTravel->getId());
        self::assertEquals($travelDetails->getDate(), $backTravelDetails->getDate());
        self::assertEquals($travelDetails->getTransportType(), $backTravelDetails->getTransportType());
        self::assertEquals($travelDetails->getEndPlace(), $backTravelDetails->getStartPlace());
        self::assertEquals($travelDetails->getStartPlace(), $backTravelDetails->getEndPlace());
    }

    private function createCommand(Vehicle|null $vehicle = null): Command
    {
        return new Command(
            10,
            $vehicle ?? Mockery::mock(Vehicle::class),
            new Passenger('Frantisek Masa', '777777777', 'Brno'),
            'Cesta na střediskovku',
            'Brno',
            '',
            Money::CZK(3120),
            Money::CZK(500),
            '',
            null,
            [],
            '',
        );
    }

    private function getDetails(): TravelDetails
    {
        return new TravelDetails(ChronosDate::now(), TransportType::get(TransportType::CAR), 'Brno', 'Praha');
    }
}
