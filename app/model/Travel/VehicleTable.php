<?php

namespace Model;
use Dibi\Connection;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa vozidel u cestovních dokladů
 */
class VehicleTable extends BaseTable {
    
    const LABEL = "CONCAT(type,' (', spz,')')";

	/** @var \ReflectionProperty */
	private $idProperty;

	public function __construct(Connection $connection)
	{
		parent::__construct($connection);
		$reflection = new \ReflectionClass('Model\Travel\Vehicle');
		$this->idProperty = $reflection->getProperty('id');
		$this->idProperty->setAccessible(TRUE);
	}

	/**
	 * @param int $id
	 * @throws VehicleNotFoundException
	 * @return Vehicle
	 */
	public function get($id)
	{
		$row = $this->connection->select('*')
			->from(self::TABLE_TC_VEHICLE)
			->where('id = %i', $id)
			->fetch();

		if(!$row) {
			throw new VehicleNotFoundException;
		}

		return $this->hydrate($row, $this->countCommands([$id])[$id]);
	}

	/**
	 * @param $unitId
	 * @param bool $archived
	 * @return Vehicle[]
	 */
    public function getAll($unitId, $archived = FALSE)
	{
		$rows = $this->connection->select('*')
			->from(self::TABLE_TC_VEHICLE)
			->where('deleted != 1')
			->where('archived = %b', $archived)
			->where('unit_id = %i', $unitId)
			->execute()
			->fetchAll('id');

		if(!$rows) {
			return [];
		}

		$indexedRows = [];
		foreach($rows as $row) {
			$indexedRows[$row->id] = $row;
		}

		$commandCounts = $this->countCommands(array_keys($indexedRows));

        $vehicles = [];
		foreach($indexedRows as $id => $row) {
			$vehicles[] = $this->hydrate($row, $commandCounts[$id]);
		}
		return $vehicles;
    }

	/**
	 * @param int $unitId
	 * @return array
	 */
    public function getPairs($unitId)
	{
		return $this->connection->select('id')
			->select(self::LABEL)
			->from(self::TABLE_TC_VEHICLE)
			->where('unit_id = %i', $unitId)
			->where('deleted = 0')
			->fetchPairs();
    }

	/**
	 * @param Vehicle $vehicle
	 */
    public function save(Vehicle $vehicle)
	{
		if($vehicle->getId()) {
			$this->connection->update(self::TABLE_TC_VEHICLE, [
				'archived' => $vehicle->isArchived(),
			])->execute();
			return;
		}

		$id = $this->connection->insert(self::TABLE_TC_VEHICLE, [
			'type' => $vehicle->getType(),
			'unit_id' => $vehicle->getUnitId(),
			'spz' => $vehicle->getRegistration(),
			'consumption' => $vehicle->getConsumption(),
		])->execute(\dibi::IDENTIFIER);
		$this->injectId($vehicle, $id);
	}

	/**
	 * Removes vehicle with specified ID
	 * @param $vehicleId
	 * @return bool
	 */
    public function remove($vehicleId)
	{
        return (bool)$this->connection->update(self::TABLE_TC_VEHICLE, ['deleted' => 1])
			->where('id = %i', $vehicleId)->execute();
    }

	/**
	 * @param Vehicle $vehicle
	 * @param int $id
	 */
    private function injectId(Vehicle $vehicle, $id)
	{
		$this->idProperty->setValue($vehicle, $id);
	}

	/**
	 * @param object $row
	 * @param int $commandsCount
	 * @return Vehicle
	 */
	private function hydrate($row, $commandsCount)
	{
		$vehicle = new Vehicle($row->type, $row->unit_id, $row->spz, $row->consumption, $commandsCount);

		$this->injectId($vehicle, $row->id);

		if($row->archived) {
			$vehicle->archive();
		}

		return $vehicle;
	}

	/**
	 * @param array $vehicleIds
	 * @return int[]
	 */
	private function countCommands(array $vehicleIds)
	{
		$counts = $this->connection->select('vehicle_id, COUNT(id) as commandsCount')
			->from(self::TABLE_TC_COMMANDS)
			->where('vehicle_id IN (%i)', $vehicleIds)
			->groupBy('vehicle_id')
			->execute()
			->fetchPairs('vehicle_id', 'commandsCount');

		// Add vehicles without commands
		$counts += array_fill_keys(array_diff($vehicleIds, array_keys($counts)), 0);

		return $counts;
	}

}
