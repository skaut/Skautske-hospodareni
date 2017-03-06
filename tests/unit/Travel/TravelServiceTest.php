<?php

namespace Model\Travel;

use Mockery as m;
use Model\CommandTable;
use Model\ContractTable;
use Model\Travel\Repositories\IVehicleRepository;
use Model\TravelService;
use Model\TravelTable;

class TravelServiceTest extends \Codeception\Test\Unit
{

    /** @var TravelService */
    private $service;

    /** @var m\MockInterface */
    private $vehicles;

    protected function _before()
    {
        $this->vehicles = m::mock(IVehicleRepository::class);
        $commands = m::mock(CommandTable::class);
        $travels = m::mock(TravelTable::class);
        $contracts = m::mock(ContractTable::class);

        $this->service = new TravelService($commands, $travels, $contracts, $this->vehicles);
    }

    public function testCreateVehicle()
    {
        $data = [
            'type' => 'Naše skvělé auto',
            'registration' => '666 045S',
            'consumption' => 12.50,
            'unit_id' => 154,
        ];

        $this->vehicles->shouldReceive('save')
            ->once()
            ->with(m::on(function (Vehicle $vehicle) use ($data) {
                $this->assertNull($vehicle->getId());
                $this->assertSame($data['type'], $vehicle->getType());
                $this->assertSame($data['registration'], $vehicle->getRegistration());
                $this->assertSame($data['consumption'], $vehicle->getConsumption());
                $this->assertSame($data['unit_id'], $vehicle->getUnitId());

                return TRUE;
            }));

        $this->service->addVehicle($data);
    }

    public function testRemoveVehicle()
    {
        $id = 15;

        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('getCommandsCount')->once()->andReturn(0);

        $this->vehicles->shouldReceive('get')
            ->once()
            ->with($id)
            ->andReturn($vehicle);

        $this->vehicles->shouldReceive('remove')
            ->once()
            ->with($id)
            ->andReturn(TRUE);

        $this->assertTrue($this->service->removeVehicle($id));
    }

    public function testRemoveVehicleWithCommandsShouldReturnFALSE()
    {
        $id = 16;

        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('getCommandsCount')->once()->andReturn(50);
        $this->vehicles->shouldReceive('get')
            ->once()
            ->with($id)
            ->andReturn($vehicle);

        $this->assertFalse($this->service->removeVehicle($id));
    }

    public function testGetAllVehicles()
    {
        $unitId = 20;

        $result = [
            $this->mockVehicle(),
            $this->mockVehicle(),
            $this->mockVehicle()
        ];

        $this->vehicles->shouldReceive('getAll')
            ->once()
            ->with($unitId)
            ->andReturn($result);

        $this->assertSame($result, $this->service->getAllVehicles($unitId));
    }

    public function testGetVehicle()
    {
        $id = 666;

        $vehicle = $this->mockVehicle();

        $this->vehicles->shouldReceive('get')
            ->once()
            ->with($id)
            ->andReturn($vehicle);

        $this->assertSame($vehicle, $this->service->getVehicle($id));
    }

    public function testArchiveVehicle()
    {
        $id = 668;

        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('isArchived')
            ->once()
            ->andReturn(FALSE);

        $vehicle->shouldReceive('archive')
            ->once();

        $this->vehicles->shouldReceive('get')
            ->once()
            ->with($id)
            ->andReturn($vehicle);

        $this->vehicles->shouldReceive('save')
            ->once()
            ->with($vehicle);

        $this->service->archiveVehicle($id);
    }

    public function testArchivedVehicleShouldntBeArchivedAgain()
    {
        $id = 667;

        $vehicle = $this->mockVehicle();
        $vehicle->shouldReceive('isArchived')
            ->once()
            ->andReturn(TRUE);

        $this->vehicles->shouldReceive('get')
            ->once()
            ->with($id)
            ->andReturn($vehicle);

        $this->service->archiveVehicle($id);
    }

    public function testGetVehiclePairs()
    {
        $unitId = 666;

        $result = [
            1 => 'Střediskové auto (3B3 4531)',
            7 => 'Středisková dodávka (3B3 3636)',
        ];

        $this->vehicles->shouldReceive('getPairs')
            ->once()
            ->with($unitId)
            ->andReturn($result);
        
        $this->assertSame($result, $this->service->getVehiclesPairs($unitId));
    }

    private function mockVehicle()
    {
        return m::mock(Vehicle::class);
    }

    private function createService() : TravelService
    {

    }

}
