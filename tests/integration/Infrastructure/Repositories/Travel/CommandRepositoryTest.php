<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Cake\Chronos\Date;
use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use IntegrationTest;
use Model\Travel\Command;
use Model\Travel\Travel\TransportType;
use Model\Travel\Vehicle;
use Money\Money;
use Nette\Utils\Json;
use function array_diff_key;
use function array_map;

class CommandRepositoryTest extends IntegrationTest
{
    private const COMMAND_ID = 1;

    private const COMMAND = [
        'unit_id' => 10,
        'purpose' => 'Převoz materiálu na tábor',
        'place' => 'Krno',
        'passengers' => 'František Maša sr.',
        'vehicle_id' => null,
        'fuel_price' => 1500,
        'amortization' => 300,
        'note' => 'Poznámka',
        'driver_name' => 'František Maša',
        'driver_contact' => '123456789',
        'driver_address' => '---',
        'next_travel_id' => 2,
        'transport_types' => '[]',
        'contract_id' => 6,
        'closed' => '2018-01-01 10:30:33',
        'unit' => '',
    ];

    private const VEHICLE_TRAVEL = [
        'id' => 0,
        'command_id' => self::COMMAND_ID,
        'start_place' => 'Brno',
        'end_place' => 'Praha',
        'distance' => 205.0,
        'type' => TransportType::CAR,
        'has_fuel' => 1,
        'start_date' => '2018-01-01',
    ];

    private const TRANSPORT_TRAVEL = [
        'id' => 1,
        'command_id' => self::COMMAND_ID,
        'start_place' => 'Praha',
        'end_place' => 'Brno',
        'price' => 500.0,
        'type' => TransportType::BUS,
        'has_fuel' => 0,
        'start_date' => '2018-01-01',
    ];

    private CommandRepository $repository;

    /**
     * @return string[]
     */
    public function getTestedAggregateRoots() : array
    {
        return [
            Command::class,
            Vehicle::class, // right now there is direct reference between the two
        ];
    }

    protected function _before() : void
    {
        parent::_before();
        $this->repository = new CommandRepository($this->tester->grabService(EntityManager::class));
    }

    public function testFindByContract() : void
    {
        $now      = '2018-01-01 00:00:00';
        $commands = [
            ['contract_id' => 1, 'closed' => $now],    // command #1
            ['contract_id' => 2, 'closed' => null],    // command #2
            ['contract_id' => 1, 'closed' => null],    // command #3
            ['contract_id' => 1, 'closed' => $now],    // command #4
            ['contract_id' => 1, 'closed' => null],    // command #5
            ['contract_id' => null, 'closed' => null],  // command #6
        ];

        foreach ($commands as $command) {
            $rowTemplate = [
                'unit_id' => 10,
                'purpose' => 'Převoz materiálu na tábor',
                'place' => 'Krno',
                'passengers' => 'František Maša sr.',
                'vehicle_id' => null,
                'fuel_price' => 0,
                'amortization' => 0,
                'note' => 'Poznámka',
                'driver_name' => 'František Maša',
                'driver_contact' => '123456789',
                'driver_address' => '---',
                'next_travel_id' => 1,
                'transport_types' => Json::encode([TransportType::CAR]),
                'unit' => '',
            ];

            $this->tester->haveInDatabase('tc_commands', $command + $rowTemplate);
        }

        $actualCommands = $this->repository->findByContract(1);

        $expectedCommandIds = [
            5,
            3, // not closed commands sorted by ID desc
            4,
            1, // closed commands sorted by ID desc
        ];

        foreach ($expectedCommandIds as $index => $commandId) {
            $this->assertSame($commandId, $actualCommands[$index]->getId());
        }
    }

    public function testFindReturnsCorrectlyHydratedAggregate() : void
    {
        $this->createCommandWithTwoTravels();

        $command = $this->repository->find(1);

        $this->assertSame(self::COMMAND['unit_id'], $command->getUnitId());
        $this->assertSame(self::COMMAND['purpose'], $command->getPurpose());
        $this->assertSame(self::COMMAND['passengers'], $command->getFellowPassengers());
        $this->assertEquals(Money::CZK(self::COMMAND['fuel_price']), $command->getFuelPrice());
        $this->assertEquals(Money::CZK(self::COMMAND['amortization']), $command->getAmortization());
        $this->assertEquals(new DateTimeImmutable(self::COMMAND['closed']), $command->getClosedAt());
        $this->assertSame(self::COMMAND['note'], $command->getNote());

        $passenger = $command->getPassenger();
        $this->assertSame(self::COMMAND['driver_name'], $passenger->getName());
        $this->assertSame(self::COMMAND['driver_contact'], $passenger->getContact());
        $this->assertSame(self::COMMAND['contract_id'], $passenger->getContractId());
        $this->assertSame(self::COMMAND['driver_address'], $passenger->getAddress());

        $travels = $command->getTravels();
        $this->assertCount(2, $travels);

        /** @var Command\VehicleTravel $vehicleTravel */
        $vehicleTravel = $travels[0];
        $this->assertSame(0, $vehicleTravel->getId());
        $details1 = $vehicleTravel->getDetails();
        $this->assertInstanceOf(Command\VehicleTravel::class, $vehicleTravel);
        $this->assertSame(self::VEHICLE_TRAVEL['distance'], $vehicleTravel->getDistance());
        $this->assertTrue($details1->getDate()->eq(new Date(self::VEHICLE_TRAVEL['start_date'])));
        $this->assertSame(self::VEHICLE_TRAVEL['start_place'], $details1->getStartPlace());
        $this->assertSame(self::VEHICLE_TRAVEL['end_place'], $details1->getEndPlace());
        $this->assertSame(TransportType::get(self::VEHICLE_TRAVEL['type']), $details1->getTransportType());

        /** @var Command\TransportTravel $transportTravel */
        $transportTravel = $travels[1];
        $this->assertSame(1, $transportTravel->getId());
        $details2 = $transportTravel->getDetails();
        $this->assertInstanceOf(Command\TransportTravel::class, $transportTravel);
        $this->assertEquals(Money::CZK(self::TRANSPORT_TRAVEL['price']), $transportTravel->getPrice());
        $this->assertTrue($details2->getDate()->eq(new Date(self::TRANSPORT_TRAVEL['start_date'])));
        $this->assertSame(self::TRANSPORT_TRAVEL['start_place'], $details2->getStartPlace());
        $this->assertSame(self::TRANSPORT_TRAVEL['end_place'], $details2->getEndPlace());
        $this->assertSame(TransportType::get(self::TRANSPORT_TRAVEL['type']), $details2->getTransportType());
    }

    public function testNextTravelId() : void
    {
        $this->createCommandWithTwoTravels();
        $command = $this->repository->find(1);
        $command->open();
        $command->addVehicleTravel(500, $command->getTravels()[0]->getDetails());

        $this->assertSame(2, $command->getTravels()[2]->getId());
    }

    public function testRemoveDeletesAggregateFromDatabase() : void
    {
        $this->createCommandWithTwoTravels();

        $command = $this->repository->find(1);

        $this->repository->remove($command);

        $this->tester->dontSeeInDatabase('tc_commands', ['id' => 1]);
        $this->tester->dontSeeInDatabase('tc_travels', ['id' => 1]);
        $this->tester->dontSeeInDatabase('tc_travels', ['id' => 2]);
    }

    public function testSaveAddsAggregateToDatabase() : void
    {
        $this->createCommandWithTwoTravels();
        $command = $this->repository->find(1);
        $this->repository->remove($command);

        $this->repository->save($command);

        // Not checking transport_types because Codeception makes weird select there
        $this->tester->seeInDatabase('tc_commands', ['id' => 2] + array_diff_key(self::COMMAND, ['transport_types' => null]));
    }

    public function testTwoCommandsCanHaveTravelsWithSameIds() : void
    {
        $this->createCommandWithTwoTravels(1);
        $this->createCommandWithTwoTravels(2);

        foreach ($this->repository->findByUnit(10) as $command) {
            $this->assertSame(2, $command->getTravelCount());
            $this->assertSame(0, $command->getTravels()[0]->getId());
            $this->assertSame(1, $command->getTravels()[1]->getId());
        }
    }

    /**
     * @return mixed[]
     */
    public function getExpectedReturnedCommandIds() : array
    {
        return [
            [2, 3, []],
            [1, 3, [5]],
            [2, 4, [6]],
            [1, 4, [5, 6]],
        ];
    }

    /**
     * @param int[] $expectedCommandIds
     *
     * @dataProvider getExpectedReturnedCommandIds
     */
    public function testFindCommandsByUnitOrUser(int $unitId, int $userId, array $expectedCommandIds) : void
    {
        $this->tester->haveInDatabase('tc_commands', ['unit_id' => 1, 'owner_id' => 999, 'id' => 5] + self::COMMAND);
        $this->tester->haveInDatabase('tc_commands', ['unit_id' => 999, 'owner_id' => 4, 'id' => 6] + self::COMMAND);
        $commands = $this->repository->findByUnitAndUser($unitId, $userId);
        $this->assertSame(
            $expectedCommandIds,
            array_map(static function (Command $command) : int {
                return $command->getId();
            }, $commands)
        );
    }

    private function createCommandWithTwoTravels(int $commandId = self::COMMAND_ID) : void
    {
        $this->tester->haveInDatabase('tc_commands', self::COMMAND);
        $this->tester->haveInDatabase('tc_travels', ['command_id' => $commandId] + self::VEHICLE_TRAVEL);
        $this->tester->haveInDatabase('tc_travels', ['command_id' => $commandId] + self::TRANSPORT_TRAVEL);
    }
}
