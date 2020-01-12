<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Travel;

use DateTimeImmutable;
use Doctrine\ORM\EntityManager;
use IntegrationTest;
use Mockery as m;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFound;
use Model\Unit\Unit;

class VehicleRepositoryTest extends IntegrationTest
{
    private const TABLE                 = 'tc_vehicle';
    private const TABLE_ROADWORTHY_SCAN = 'tc_vehicle_roadworthy_scan';

    /** @var VehicleRepository */
    private $repository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Vehicle::class,
            Vehicle\RoadworthyScan::class,
        ];
    }

    protected function _before() : void
    {
        parent::_before();
        $this->repository = new VehicleRepository($this->tester->grabService(EntityManager::class));
    }

    public function testFindByUnitWithNoVehiclesReturnsEmptyArray() : void
    {
        $this->assertEmpty($this->repository->findByUnit(10));
    }

    public function testFindByUnitReturnsOnlyVehiclesWithCorrectUnit() : void
    {
        $I = $this->tester;

        $expectedVehicles = [
            [
                'type' => 'Car',
                'registration' => '123456',
                'unit_id' => 5,
                'consumption' => 5.5,
                'note' => 'note',
                'archived' => 0,
                'metadata_created_at' => '2017-10-10',
                'metadata_author_name' => 'František Maša',
            ],
            [
                'type' => 'Car ě',
                'registration' => '666',
                'unit_id' => 5,
                'consumption' => 6.5,
                'note' => 'note',
                'archived' => 0,
                'metadata_created_at' => '2016-11-17',
                'metadata_author_name' => 'František Hána',
            ],
        ];

        $I->haveInDatabase(self::TABLE, $expectedVehicles[0]);
        $I->haveInDatabase(self::TABLE, $expectedVehicles[1]);
        $I->haveInDatabase(self::TABLE, [ // This one doesn't belong to unit 5
            'type' => 'Car 3',
            'registration' => '6666',
            'unit_id' => 4,
            'consumption' => 4.5,
            'note' => 'note',
            'archived' => 0,
            'metadata_created_at' => '2000-10-12',
            'metadata_author_name' => 'František Hána',
        ]);

        $vehicles = $this->repository->findByUnit(5);

        $this->assertCount(2, $vehicles);

        $this->assertSame(1, $vehicles[0]->getId());
        $this->assertSame(2, $vehicles[1]->getId());
    }

    public function testFindNonExistentVehicleThrowsException() : void
    {
        $this->expectException(VehicleNotFound::class);

        $this->repository->find(1);
    }

    public function testFindReturnsCorrectlyMappedEntity() : void
    {
        $row = $this->getVehicleRow();

        $roadworthies = [
            [
                'vehicle_id' => 1,
                'file_path' => 'roadworthy1.jpg',
            ],
            [
                'vehicle_id' => 1,
                'file_path' => 'roadworthy1.jpg',
            ],
        ];

        $this->tester->haveInDatabase(self::TABLE, $row);

        foreach ($roadworthies as $roadworthy) {
            $this->tester->haveInDatabase(self::TABLE_ROADWORTHY_SCAN, $roadworthy);
        }

        $vehicle = $this->repository->find(1);

        $this->assertSame(1, $vehicle->getId());
        $this->assertSame($row['type'], $vehicle->getType());
        $this->assertSame($row['registration'], $vehicle->getRegistration());
        $this->assertSame($row['unit_id'], $vehicle->getUnitId());
        $this->assertSame($row['subunit_id'], $vehicle->getSubunitId());
        $this->assertSame($row['consumption'], $vehicle->getConsumption());
        $this->assertSame($row['note'], $vehicle->getNote());
        $this->assertFalse($vehicle->isArchived());
        $this->assertEquals(
            new DateTimeImmutable($row['metadata_created_at']),
            $vehicle->getMetadata()->getCreatedAt()
        );
        $this->assertSame(
            $row['metadata_author_name'],
            $vehicle->getMetadata()->getAuthorName()
        );

        $roadworthyScans = $vehicle->getRoadworthyScans();
        $this->assertCount(2, $vehicle->getRoadworthyScans());
        $this->assertSame($roadworthies[0]['file_path'], $roadworthyScans[0]->getFilePath()->getPath());
        $this->assertSame($roadworthies[1]['file_path'], $roadworthyScans[1]->getFilePath()->getPath());
    }

    public function testRemove() : void
    {
        $this->tester->haveInDatabase(self::TABLE, $this->getVehicleRow());

        $vehicle = $this->repository->find(1);

        $this->repository->remove($vehicle);

        $this->tester->dontSeeInDatabase(self::TABLE, ['id' => 1]);
    }

    public function testSave() : void
    {
        $row = $this->getVehicleRow();

        $unit    = m::mock(Unit::class, ['getId' => $row['unit_id']]);
        $subunit = m::mock(Unit::class, ['getId' => $row['subunit_id']]);

        $vehicle = new Vehicle(
            $row['type'],
            $unit,
            $subunit,
            $row['registration'],
            $row['consumption'],
            new Vehicle\Metadata(new DateTimeImmutable($row['metadata_created_at']), $row['metadata_author_name'])
        );

        $this->repository->save($vehicle);

        $this->tester->seeInDatabase(self::TABLE, ['id' => 1] + $row);
    }

    /**
     * @return mixed[]
     */
    private function getVehicleRow() : array
    {
        return [
            'type' => 'Car 3',
            'registration' => '6666',
            'unit_id' => 4,
            'subunit_id' => 11,
            'consumption' => 4.5,
            'note' => '',
            'archived' => 0,
            'metadata_created_at' => '2000-10-12 00:00:00',
            'metadata_author_name' => 'Frantisek Hana',
        ];
    }
}
