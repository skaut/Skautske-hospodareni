<?php

namespace Model;
use Model\Travel\Vehicle;
use Model\Travel\VehicleNotFoundException;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa vozidel u cestovních dokladů
 */
class VehicleTable extends BaseTable {
    
    const LABEL = "CONCAT(type,' (', spz,')')";

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
	 * @return Vehicle[]
	 */
    public function getAll($unitId)
	{
		$rows = $this->connection->select('*')
			->from(self::TABLE_TC_VEHICLE)
			->where('deleted != 1')
			->where('unit_id = %i', $unitId)
			->execute()
			->fetchAll('id');

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
		return $this->connection->select('id', self::LABEL)
			->from(self::TABLE_TC_VEHICLE)
			->where('unit_id = %i', $unitId)
			->where('deleted = 0')
			->fetchPairs();
    }
    
    public function add($data)
	{
        return $this->connection->insert(self::TABLE_TC_VEHICLE, $data)->execute();
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
	 * @param object $row
	 * @param int $commandsCount
	 * @return Vehicle
	 */
	private function hydrate($row, $commandsCount)
	{
		return new Vehicle($row->id, $row->type, $row->unit_id, $row->spz, $row->consumption, $commandsCount);
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
