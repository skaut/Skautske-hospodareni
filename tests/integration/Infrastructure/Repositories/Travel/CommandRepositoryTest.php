<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Command;
use Model\Travel\Vehicle;
use Model\Utils\MoneyFactory;

class CommandRepositoryTest extends \IntegrationTest
{
    private const COMMAND = [
        'unit_id' => 10,
        'purpose' => 'Převoz materiálu na tábor',
        'place' => 'Krno',
        'passengers' => 'František Maša sr.',
        'vehicle_id' => null,
        'fuel_price' => 15,
        'amortization' => 3,
        'note' => 'Poznámka',
        'driver_name' => 'František Maša',
        'driver_contact' => '123456789',
        'driver_address' => '---',
        'next_travel_id' => 2,
        'contract_id' => 6,
        'closed' => '2018-01-01 10:30:33',
    ];

    private const VEHICLE_TRAVEL = [
        'id' => 0,
        'command_id' => 1,
        'start_place' => 'Brno',
        'end_place' => 'Praha',
        'distance' => 205.0,
        'type' => 'auv',
        'has_fuel' => 1,
        'start_date' => '2018-01-01',
    ];

    private const TRANSPORT_TRAVEL = [
        'id' => 1,
        'command_id' => 1,
        'start_place' => 'Praha',
        'end_place' => 'Brno',
        'distance' => 500.0,
        'type' => 'a',
        'has_fuel' => 0,
        'start_date' => '2018-01-01',
    ];

    /** @var CommandRepository */
    private $repository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Command::class,
            Command\Travel::class,
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
        $this->assertEquals(MoneyFactory::fromFloat(self::COMMAND['fuel_price']), $command->getFuelPrice());
        $this->assertEquals(MoneyFactory::fromFloat(self::COMMAND['amortization']), $command->getAmortization());
        $this->assertEquals(new \DateTimeImmutable(self::COMMAND['closed']), $command->getClosedAt());
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
        $this->assertEquals(new \DateTimeImmutable(self::VEHICLE_TRAVEL['start_date']), $details1->getDate());
        $this->assertSame(self::VEHICLE_TRAVEL['start_place'], $details1->getStartPlace());
        $this->assertSame(self::VEHICLE_TRAVEL['end_place'], $details1->getEndPlace());
        $this->assertSame(self::VEHICLE_TRAVEL['type'], $details1->getTransportType());

        /** @var Command\TransportTravel $transportTravel */
        $transportTravel = $travels[1];
        $this->assertSame(1, $transportTravel->getId());
        $details2 = $transportTravel->getDetails();
        $this->assertInstanceOf(Command\TransportTravel::class, $transportTravel);
        $this->assertEquals(MoneyFactory::fromFloat(self::TRANSPORT_TRAVEL['distance']), $transportTravel->getPrice());
        $this->assertEquals(new \DateTimeImmutable(self::TRANSPORT_TRAVEL['start_date']), $details2->getDate());
        $this->assertSame(self::TRANSPORT_TRAVEL['start_place'], $details2->getStartPlace());
        $this->assertSame(self::TRANSPORT_TRAVEL['end_place'], $details2->getEndPlace());
        $this->assertSame(self::TRANSPORT_TRAVEL['type'], $details2->getTransportType());
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

        $this->tester->seeInDatabase('tc_commands', ['id' => 2] + self::COMMAND);
    }

    private function createCommandWithTwoTravels() : void
    {
        $this->tester->haveInDatabase('tc_commands', self::COMMAND);
        $this->tester->haveInDatabase('tc_travels', self::VEHICLE_TRAVEL);
        $this->tester->haveInDatabase('tc_travels', self::TRANSPORT_TRAVEL);
    }
}
