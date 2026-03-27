<?php

declare(strict_types=1);

namespace App\Fixtures\Doctrine;

use App\Model\Travel\Command;
use App\Model\Travel\Command\TravelDetails;
use App\Model\Travel\Contract;
use App\Model\Travel\Contract\Passenger as ContractPassenger;
use App\Model\Travel\Passenger;
use App\Model\Travel\Travel\TransportType;
use App\Model\Travel\Vehicle;
use App\Model\Travel\Vehicle\Metadata;
use App\Model\Unit\Unit;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

final class Unit25893TravelFixture extends AbstractFixture
{
    private const UNIT_ID = 25893;

    public function load(ObjectManager $manager): void
    {
        $unit = $this->createFixtureUnit();

        // ── Vehicles ──
        $car = $this->findOrCreateVehicle($manager, $unit, 'Škoda Octavia', '1A2 3456', 6.5);
        $van = $this->findOrCreateVehicle($manager, $unit, 'Volkswagen Transporter', '2B3 4567', 9.2);

        // ── Contracts ──
        $this->findOrCreateContract($manager, $unit);

        // ── Commands ──
        $this->createOpenCommand($manager, $car, 'Výprava na Šumavu', 'Kašperské Hory');
        $this->createClosedCommand($manager, $van, 'Oddílový výlet do Brna', 'Brno');

        $manager->flush();
    }

    private function findOrCreateVehicle(ObjectManager $manager, Unit $unit, string $type, string $registration, float $consumption): Vehicle
    {
        $existing = $manager->getRepository(Vehicle::class)->findOneBy([
            'unitId' => self::UNIT_ID,
            'registration' => $registration,
        ]);

        if ($existing instanceof Vehicle) {
            return $existing;
        }

        $vehicle = new Vehicle(
            $type,
            $unit,
            null,
            $registration,
            $consumption,
            new Metadata(new DateTimeImmutable('2026-01-15 10:00:00'), 'Fixture Admin'),
        );

        $manager->persist($vehicle);

        return $vehicle;
    }

    private function findOrCreateContract(ObjectManager $manager, Unit $unit): Contract
    {
        $existing = $manager->getRepository(Contract::class)->findOneBy([
            'unitId' => self::UNIT_ID,
        ]);

        if ($existing instanceof Contract) {
            return $existing;
        }

        $contract = new Contract(
            $unit,
            'Jan Vedoucí',
            new ChronosDate('2026-01-01'),
            new ContractPassenger(
                'Karel Řidič',
                'karel@example.com',
                'Hlavní 123, Praha',
                new ChronosDate('1990-05-15'),
            ),
        );

        $manager->persist($contract);

        return $contract;
    }

    private function createOpenCommand(ObjectManager $manager, Vehicle $car, string $purpose, string $place): void
    {
        $existing = $manager->getRepository(Command::class)->findOneBy([
            'unitId' => self::UNIT_ID,
            'purpose' => $purpose,
        ]);

        if ($existing instanceof Command) {
            return;
        }

        $command = new Command(
            self::UNIT_ID,
            $car,
            new Passenger('Karel Řidič', 'karel@example.com', 'Hlavní 123, Praha'),
            $purpose,
            $place,
            '',
            MoneyFactory::fromFloat(36.10),
            MoneyFactory::fromFloat(4.10),
            'Fixture – otevřený příkaz',
            null,
            [TransportType::get(TransportType::CAR)],
            'Středisko Tábor',
        );

        $command->addVehicleTravel(85.0, new TravelDetails(
            new ChronosDate('2026-03-15'),
            TransportType::get(TransportType::CAR),
            'Praha',
            $place,
        ));
        $command->addVehicleTravel(85.0, new TravelDetails(
            new ChronosDate('2026-03-16'),
            TransportType::get(TransportType::CAR),
            $place,
            'Praha',
        ));

        $manager->persist($command);
    }

    private function createClosedCommand(ObjectManager $manager, Vehicle $van, string $purpose, string $place): void
    {
        $existing = $manager->getRepository(Command::class)->findOneBy([
            'unitId' => self::UNIT_ID,
            'purpose' => $purpose,
        ]);

        if ($existing instanceof Command) {
            return;
        }

        $command = new Command(
            self::UNIT_ID,
            $van,
            new Passenger('Petra Řidičová', 'petra@example.com', 'Nádražní 45, Tábor'),
            $purpose,
            $place,
            'Josef, Marie',
            MoneyFactory::fromFloat(36.10),
            MoneyFactory::fromFloat(4.10),
            'Fixture – uzavřený příkaz',
            null,
            [TransportType::get(TransportType::CAR)],
            'Středisko Tábor',
        );

        $command->addVehicleTravel(200.0, new TravelDetails(
            new ChronosDate('2026-02-10'),
            TransportType::get(TransportType::CAR),
            'Tábor',
            $place,
        ));
        $command->addVehicleTravel(200.0, new TravelDetails(
            new ChronosDate('2026-02-11'),
            TransportType::get(TransportType::CAR),
            $place,
            'Tábor',
        ));
        $command->addTransportTravel(MoneyFactory::fromFloat(150.0), new TravelDetails(
            new ChronosDate('2026-02-10'),
            TransportType::get(TransportType::BUS),
            'Tábor',
            $place,
        ));

        $command->close(new DateTimeImmutable('2026-02-15 18:00:00'));
        $manager->persist($command);
    }

    private function createFixtureUnit(): Unit
    {
        return new Unit(
            self::UNIT_ID,
            'Středisko Tábor',
            'Středisko Tábor',
            '12345678',
            'Husova 10',
            'Tábor',
            '390 01',
            '621.01',
            'stredisko',
        );
    }
}
