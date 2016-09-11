<?php

namespace Model;
use Model\Travel\Vehicle;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa vozidel u cestovních dokladů
 */
class VehicleTable extends BaseTable {
    
    const LABEL = "CONCAT(type,' (', spz,')')";

	/**
	 * @param int $id
	 * @return Vehicle
	 */
	public function getObject($id)
	{
		$id = (int)$id;
		$row = $this->get($id);
		return new Vehicle($id, $row->unit_id, $row->spz, $row->consumption);
	}

    public function get($vehicleId, $withDeleted = false) {
        return $this->connection->fetch("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id=%i", $vehicleId, "%if", !$withDeleted," AND deleted=0 %end", " LIMIT 1");
    }
    
    public function getAll($unitId) {
        return $this->connection->fetchAll("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE unit_id=%i", $unitId, " AND deleted=0");
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
