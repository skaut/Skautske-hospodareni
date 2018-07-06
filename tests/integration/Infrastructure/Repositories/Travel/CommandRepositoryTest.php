<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Doctrine\ORM\EntityManager;
use Model\Travel\Command;
use Model\Travel\Vehicle;

class CommandRepositoryTest extends \IntegrationTest
{
    /** @var CommandRepository */
    private $repository;

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
}
