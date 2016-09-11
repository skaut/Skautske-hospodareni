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
	 * @param object $row
	 * @return Vehicle
	 */
	private function hydrate($row, $commandsCount = 0)
	{
		return new Vehicle($row->id, $row->type, $row->unit_id, $row->spz, $row->consumption, $commandsCount);
	}

	/**
	 * @param int $id
	 * @throws VehicleNotFoundException
	 * @return Vehicle
	 */
	public function getObject($id)
	{
		$id = (int)$id;
		$row = $this->get($id);

		if(!$row) {
			throw new VehicleNotFoundException;
		}
		return $this->hydrate($row, $this->countCommands([$id])[$id]);
	}

    private function get($vehicleId)
	{
        return $this->connection->fetch("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id=%i", $vehicleId, " LIMIT 1");
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

	/**
	 * @param $unitId
	 * @return Vehicle[]
	 */
    public function getAll($unitId)
	{
		$rows = $this->connection->select('*')
			->from(self::TABLE_TC_VEHICLE, 'AS vehicle')
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
    
    public function getPairs($unitId) {
        return $this->connection->fetchPairs("SELECT id, ", self::LABEL," FROM [" . self::TABLE_TC_VEHICLE . "] WHERE unit_id=%i", $unitId, " AND deleted=0");
    }
    
    public function add($data) {
        return $this->connection->insert(self::TABLE_TC_VEHICLE, $data)->execute();
    }
    
    public function remove($vehicleId) {
        return $this->connection->update(self::TABLE_TC_VEHICLE, array("deleted"=>1))->where("id=%i", $vehicleId)->execute();
    }
}
