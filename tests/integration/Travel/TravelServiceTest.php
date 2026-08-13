<?php

declare(strict_types=1);

namespace App\Model\Travel;

use App\Model\Common\Services\QueryBus;
use App\Model\Infrastructure\Repositories\Travel\CommandRepository;
use App\Model\Infrastructure\Repositories\Travel\ContractRepository;
use App\Model\Infrastructure\Repositories\Travel\VehicleRepository;
use App\Model\Travel\Travel\TransportType;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use IntegrationTest;
use InvalidArgumentException;
use Mockery as m;

use function array_keys;
use function count;

/**
 * TravelService nad reálnými repozitáři a databází — cestovní příkazy, vozidla a smlouvy.
 */
final class TravelServiceTest extends IntegrationTest
{
    private const UNIT_ID = 5;
    private const OWNER_ID = 77;

    private TravelService $service;

    private VehicleRepository $vehicles;

    private ContractRepository $contracts;

    private CommandRepository $commands;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [Vehicle::class, Command::class, Contract::class];
    }

    protected function _before(): void
    {
        parent::_before();

        $entityManager = $this->tester->grabService(EntityManager::class);

        $this->vehicles = new VehicleRepository($entityManager);
        $this->commands = new CommandRepository($entityManager);
        $this->contracts = new ContractRepository($entityManager);

        $units = m::mock(IUnitRepository::class);
        $units->shouldReceive('find')->andReturn(m::mock(Unit::class, ['getId' => self::UNIT_ID]));

        $this->service = new TravelService(
            $this->vehicles,
            $this->commands,
            $this->contracts,
            $units,
            m::mock(QueryBus::class),
        );
    }

    public function testVehicleDtoIsBuiltFromEntityAndMissingVehicleReturnsNull(): void
    {
        $id = $this->createVehicle();

        $vehicle = $this->service->getVehicleDTO($id);

        self::assertNotNull($vehicle);
        self::assertSame('Škoda Octavia', $vehicle->getType());
        self::assertSame('1AB 2345', $vehicle->getRegistration());

        self::assertNull($this->service->getVehicleDTO($id + 100));
        self::assertNull($this->service->findVehicle($id + 100));
        self::assertSame($id, $this->service->findVehicle($id)?->getId());
    }

    public function testVehicleListsCoverPairsDtosAndFilter(): void
    {
        $first = $this->createVehicle();
        $second = $this->createVehicle('Ford Focus', '9XY 8765');
        $this->createVehicle('Cizí auto', '0ZZ 0000', unitId: 99);

        self::assertSame([$first, $second], array_keys($this->service->getVehiclesPairs(self::UNIT_ID)));
        self::assertCount(2, $this->service->getAllVehicles(self::UNIT_ID));
        self::assertCount(2, $this->service->getVehiclesByFilter(self::UNIT_ID)->getQuery()->getResult());
        self::assertCount(2, $this->service->findVehiclesByIds([$first, $second]));
    }

    public function testArchivedVehicleDropsOutOfFilterAndSecondArchivingIsNoOp(): void
    {
        $id = $this->createVehicle();

        $this->service->archiveVehicle($id);
        $this->service->archiveVehicle($id);

        self::assertTrue($this->service->findVehicle($id)?->isArchived());
        self::assertCount(0, $this->service->getVehiclesByFilter(self::UNIT_ID)->getQuery()->getResult());
    }

    public function testVehicleWithCommandCannotBeRemoved(): void
    {
        $vehicleId = $this->createVehicle();
        $this->createCommand($vehicleId);

        self::assertSame(1, $this->service->getCommandsCount($vehicleId));

        $this->expectException(VehicleLinkedRecord::class);

        $this->service->removeVehicle($vehicleId);
    }

    public function testVehicleWithoutCommandIsRemoved(): void
    {
        $vehicleId = $this->createVehicle();

        $this->service->removeVehicle($vehicleId);

        self::assertNull($this->service->findVehicle($vehicleId));
    }

    public function testCommandIsCreatedFromContractPassengerAndListedForUnit(): void
    {
        $contractId = $this->createContract();
        $vehicleId = $this->createVehicle();

        $this->service->addCommand(
            self::UNIT_ID,
            $contractId,
            null,
            $vehicleId,
            'Cesta na výpravu',
            'Beskydy',
            'Jan, Eva',
            MoneyFactory::fromFloat(38.5),
            MoneyFactory::fromFloat(4.2),
            'poznámka',
            [TransportType::get(TransportType::CAR)],
            self::OWNER_ID,
            'Středisko Test',
        );

        $commands = $this->service->getAllCommands(self::UNIT_ID);
        self::assertCount(1, $commands);

        $command = $commands[0];
        self::assertSame('Cesta na výpravu', $command->getPurpose());
        self::assertSame('Beskydy', $command->getPlace());
        self::assertSame('Jan Řidič', $command->getPassenger()->getName(), 'řidič se převezme ze smlouvy');

        $detail = $this->service->getCommandDetail($command->getId());
        self::assertNotNull($detail);
        self::assertSame('poznámka', $detail->getNote());

        self::assertCount(1, $this->service->getAllCommandsByContract($contractId));
        self::assertCount(1, $this->service->getAllCommandsByVehicle($vehicleId));
        self::assertCount(1, $this->service->getVisibleUserCommands([self::UNIT_ID], self::OWNER_ID));
        self::assertCount(1, $this->service->getVisibleUserCommandsByVehicle($vehicleId, [self::UNIT_ID], self::OWNER_ID));
        // Viditelnost je sjednocení „vlastní příkazy“ a „příkazy z čitelných jednotek“.
        self::assertCount(1, $this->service->getVisibleUserCommands([], self::OWNER_ID), 'vlastník vidí svůj příkaz i bez čitelné jednotky');
        self::assertCount(1, $this->service->getVisibleUserCommands([self::UNIT_ID], self::OWNER_ID + 1), 'čitelná jednotka zpřístupní i cizí příkaz');
        self::assertCount(0, $this->service->getVisibleUserCommands([self::UNIT_ID + 1], self::OWNER_ID + 1));
        self::assertCount(0, $this->service->getVisibleUserCommandsByVehicle($vehicleId, [self::UNIT_ID + 1], self::OWNER_ID + 1));
    }

    public function testCommandRequiresExactlyOneOfPassengerAndContract(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->addCommand(
            self::UNIT_ID,
            null,
            null,
            null,
            'Cesta',
            'Praha',
            '',
            MoneyFactory::zero(),
            MoneyFactory::zero(),
            '',
            [TransportType::get(TransportType::TRAIN)],
            self::OWNER_ID,
            'Středisko Test',
        );
    }

    public function testCommandIsUpdatedAndKeepsAlreadyUsedTransportTypes(): void
    {
        $commandId = $this->createCommand($this->createVehicle());
        $this->service->addTravel($commandId, TransportType::get(TransportType::CAR), new ChronosDate('2026-07-02'), 'Praha', 'Brno', 200.0);

        $this->service->updateCommand(
            $commandId,
            null,
            new Passenger('Eva Nová', '+420111222333', 'Praha 1'),
            null,
            'Nový účel',
            'Brno',
            'Eva',
            MoneyFactory::fromFloat(40.0),
            MoneyFactory::fromFloat(5.0),
            'nová poznámka',
            [TransportType::get(TransportType::TRAIN)],
            'Středisko Test',
        );

        $detail = $this->service->getCommandDetail($commandId);
        self::assertNotNull($detail);
        self::assertSame('Nový účel', $detail->getPurpose());
        self::assertSame('Eva Nová', $detail->getPassenger()->getName());
        self::assertNull($detail->getVehicleId(), 'příkaz jde odpojit od vozidla');

        $types = $this->service->getCommandDetail($commandId)?->getTransportTypes() ?? [];
        self::assertCount(2, $types, 'použitý typ dopravy zůstane i po odebrání ze formuláře');
    }

    public function testTravelsAreAddedUpdatedAndRemoved(): void
    {
        $commandId = $this->createCommand($this->createVehicle());

        $this->service->addTravel($commandId, TransportType::get(TransportType::CAR), new ChronosDate('2026-07-02'), 'Praha', 'Brno', 200.0);
        $this->service->addTravel($commandId, TransportType::get(TransportType::TRAIN), new ChronosDate('2026-07-03'), 'Brno', 'Praha', 350.0);

        $travels = $this->service->getTravels($commandId);
        self::assertCount(2, $travels);
        self::assertSame('Praha', $travels[0]->getDetails()->getStartPlace());
        self::assertSame(200.0, $travels[0]->getDistance());
        self::assertNull($travels[1]->getDistance(), 'vlak nemá ujeté kilometry');
        self::assertSame('35000', $travels[1]->getPrice()->getAmount(), 'jízdenka je uložená jako cena');

        $travelId = $travels[0]->getId();

        $this->service->updateTravel(
            $commandId,
            $travelId,
            250.0,
            new ChronosDate('2026-07-04'),
            TransportType::get(TransportType::CAR),
            'Plzeň',
            'Ostrava',
        );

        $travel = $this->service->getTravel($commandId, $travelId);
        self::assertNotNull($travel);
        self::assertSame('Plzeň', $travel->getDetails()->getStartPlace());
        self::assertSame(250.0, $travel->getDistance());

        $this->service->removeTravel($commandId, $travelId);
        self::assertCount(1, $this->service->getTravels($commandId));
    }

    public function testUpdateOfMissingTravelIsIgnored(): void
    {
        $commandId = $this->createCommand($this->createVehicle());

        $this->service->updateTravel(
            $commandId,
            999,
            100.0,
            new ChronosDate('2026-07-04'),
            TransportType::get(TransportType::CAR),
            'Plzeň',
            'Ostrava',
        );

        self::assertCount(0, $this->service->getTravels($commandId));
    }

    public function testCommandIsClosedReopenedAndDeleted(): void
    {
        $commandId = $this->createCommand($this->createVehicle());

        $this->service->closeCommand($commandId);
        self::assertNotNull($this->service->getCommandDetail($commandId)?->getClosedAt());

        $this->service->openCommand($commandId);
        self::assertNull($this->service->getCommandDetail($commandId)?->getClosedAt());

        $this->service->deleteCommand($commandId);
        self::assertNull($this->service->getCommandDetail($commandId));
    }

    public function testContractsAreCreatedListedAndDeleted(): void
    {
        $this->service->createContract(
            self::UNIT_ID,
            'Karel Statutár',
            new ChronosDate('2026-01-01'),
            new Contract\Passenger('Jan Řidič', '+420123456789', 'Praha 5', new ChronosDate('1990-02-03')),
        );

        $contracts = $this->service->getAllContracts(self::UNIT_ID);
        self::assertCount(1, $contracts);

        $contractId = $contracts[0]->getId();
        self::assertSame('Jan Řidič', $this->service->getContract($contractId)?->getPassenger()->getName());
        self::assertNull($this->service->getContract($contractId + 100));

        $this->service->deleteContract($contractId);
        self::assertNull($this->service->getContract($contractId));

        // mazání neexistující smlouvy nesmí spadnout
        $this->service->deleteContract($contractId);
    }

    public function testContractPairsSplitValidAndPastContracts(): void
    {
        $this->service->createContract(
            self::UNIT_ID,
            'Karel Statutár',
            new ChronosDate('2026-01-01'),
            new Contract\Passenger('Platná Jana', '+420123456789', 'Praha 5', null),
        );
        $this->service->createContract(
            self::UNIT_ID,
            '',
            new ChronosDate('2020-01-01'),
            new Contract\Passenger('Stará Marie', '+420123456789', 'Praha 5', null),
        );

        $pairs = $this->service->getAllContractsPairs(self::UNIT_ID, null);

        self::assertCount(1, $pairs['valid']);
        self::assertSame(
            'Karel Statutár <=> Platná Jana (platná do 1.1.2029)',
            $pairs['valid'][array_keys($pairs['valid'])[0]],
        );
        self::assertCount(0, $pairs['past'], 'smlouva starší než rok se nabízí jen při explicitním vyžádání');

        $pastId = array_keys($pairs['valid'])[0] + 1;
        $pairsWithPast = $this->service->getAllContractsPairs(self::UNIT_ID, $pastId);
        self::assertCount(1, $pairsWithPast['past']);
        self::assertSame('Stará Marie (platná do 1.1.2023)', $pairsWithPast['past'][$pastId]);
    }

    private function createVehicle(string $type = 'Škoda Octavia', string $registration = '1AB 2345', int $unitId = self::UNIT_ID): int
    {
        $vehicle = new Vehicle(
            $type,
            m::mock(Unit::class, ['getId' => $unitId]),
            null,
            $registration,
            5.6,
            new Vehicle\Metadata(new DateTimeImmutable('2026-01-01'), 'Tester'),
        );

        $this->vehicles->save($vehicle);

        return $vehicle->getId();
    }

    private function createContract(): int
    {
        $this->service->createContract(
            self::UNIT_ID,
            'Karel Statutár',
            new ChronosDate('2026-01-01'),
            new Contract\Passenger('Jan Řidič', '+420123456789', 'Praha 5', new ChronosDate('1990-02-03')),
        );

        $contracts = $this->service->getAllContracts(self::UNIT_ID);

        return $contracts[count($contracts) - 1]->getId();
    }

    private function createCommand(?int $vehicleId = null): int
    {
        $this->service->addCommand(
            self::UNIT_ID,
            null,
            new Passenger('Jan Řidič', '+420123456789', 'Praha 5'),
            $vehicleId,
            'Cesta',
            'Praha',
            '',
            MoneyFactory::fromFloat(38.0),
            MoneyFactory::fromFloat(4.0),
            '',
            [TransportType::get(TransportType::CAR)],
            self::OWNER_ID,
            'Středisko Test',
        );

        $commands = $this->service->getAllCommands(self::UNIT_ID);

        return $commands[count($commands) - 1]->getId();
    }
}
