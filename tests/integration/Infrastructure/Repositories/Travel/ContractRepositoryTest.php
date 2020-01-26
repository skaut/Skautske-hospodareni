<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use Cake\Chronos\Date;
use IntegrationTest;
use Model\Travel\Contract;

final class ContractRepositoryTest extends IntegrationTest
{
    private const CONTRACT = [
        'unit_id' => 10,
        'unit_person' => 'František Maša',
        'driver_name' => 'František Hána',
        'driver_address' => 'Praha',
        'driver_birthday' => '2018-01-01',
        'driver_contact' => '777777777',
        'start' => '2018-01-01',
        'end' => '2018-01-20',
        'template' => 2,
    ];

    /** @var ContractRepository */
    private $repository;

    protected function _before() : void
    {
        parent::_before();
        $this->repository = new ContractRepository($this->entityManager);
    }

    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [Contract::class];
    }

    public function testFindReturnsCorrectlyHydratedAggregate() : void
    {
        $this->addContractToDatabase();

        $contract = $this->repository->find(1);

        $this->assertSame(1, $contract->getId());
        $this->assertSame(self::CONTRACT['unit_id'], $contract->getUnitId());
        $this->assertSame(self::CONTRACT['unit_person'], $contract->getUnitRepresentative());
        $this->assertEquals(new Date(self::CONTRACT['start']), $contract->getSince());
        $this->assertEquals(new Date(self::CONTRACT['end']), $contract->getUntil());
        $this->assertSame(self::CONTRACT['template'], $contract->getTemplateVersion());

        $passenger = $contract->getPassenger();
        $this->assertSame(self::CONTRACT['driver_name'], $passenger->getName());
        $this->assertSame(self::CONTRACT['driver_address'], $passenger->getAddress());
        $this->assertSame(self::CONTRACT['driver_contact'], $passenger->getContact());
        $this->assertSame(self::CONTRACT['driver_contact'], $passenger->getContact());
    }

    public function testRemoveDeletesRowFromDatabase() : void
    {
        $this->addContractToDatabase();
        $contract = $this->repository->find(1);

        $this->repository->remove($contract);

        $this->tester->dontSeeInDatabase('tc_contracts', ['id' => 1]);
    }

    public function testSaveAddsRowToDatabase() : void
    {
        $this->addContractToDatabase();
        $contract = $this->repository->find(1);
        $this->repository->remove($contract);

        $this->repository->save($contract);

        $this->tester->seeInDatabase('tc_contracts', ['id' => 2] + self::CONTRACT);
    }

    private function addContractToDatabase() : void
    {
        $this->tester->haveInDatabase('tc_contracts', self::CONTRACT);
    }
}
