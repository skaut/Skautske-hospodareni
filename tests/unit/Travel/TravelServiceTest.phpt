<?php

namespace Tests\Unit\Travel;

use Dibi\Connection;
use Mockery;
use Model\Travel\Vehicle;
use Model\TravelService;
use Tester\Assert;

require __DIR__.'/../../bootstrap.php';

/**
 * @testCase
 */
class TravelServiceTest extends \Tester\TestCase
{

	/** @var TravelService */
	private $service;

	/** @var Mockery\MockInterface */
	private $vehicles;

	public function setUp()
	{
		$this->vehicles = Mockery::mock('Model\Travel\Repositories\IVehicleRepository');
		$this->service = new TravelService(Mockery::mock('Dibi\Connection'), $this->vehicles);
	}

	public function tearDown()
	{
		\Mockery::close();
		Assert::true(TRUE); // Hack for tests without assertion
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
			->with(Mockery::on(function(Vehicle $vehicle) use($data) {
				Assert::null($vehicle->getId());
				Assert::same($data['type'], $vehicle->getType());
				Assert::same($data['registration'], $vehicle->getRegistration());
				Assert::same($data['consumption'], $vehicle->getConsumption());
				Assert::same($data['unit_id'], $vehicle->getUnitId());

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

		Assert::true($this->service->removeVehicle($id));
	}

	public function testRemoveVehicleWithCommandsShouldReturnFALSE()
	{
		$id = 15;

		$vehicle = $this->mockVehicle();
		$vehicle->shouldReceive('getCommandsCount')->once()->andReturn(50);

		$this->vehicles->shouldReceive('get')
			->once()
			->with($id)
			->andReturn($vehicle);

		Assert::false($this->service->removeVehicle($id));
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

		Assert::same($result, $this->service->getAllVehicles($unitId));
	}

	public function testGetVehicle()
	{
		$id = 666;

		$vehicle = $this->mockVehicle();

		$this->vehicles->shouldReceive('get')
			->once()
			->with($id)
			->andReturn($vehicle);

		Assert::same($vehicle, $this->service->getVehicle($id));
	}

	public function testArchiveVehicle()
	{
		$id = 666;

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
		$id = 666;

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

		Assert::same($result, $this->service->getVehiclesPairs($unitId));
	}

	private function mockVehicle()
	{
		return Mockery::mock('Model\Travel\Vehicle');
	}

}
(new TravelServiceTest())->run();
