<?php

/**
 * @author Hána František
 */
class VehicleTable extends BaseTable {
    
    const LABEL = "CONCAT(type,' (', spz,')')";

    public function get($vehicleId) {
        return dibi::fetch("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id=%i", $vehicleId, " AND deleted=0", " LIMIT 1");
    }
    
    public function getAll($unitId) {
        return dibi::fetchAll("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE unit_id=%i", $unitId, " AND deleted=0");
    }
    
    public function getPairs($unitId) {
        return dibi::fetchPairs("SELECT id, ", self::LABEL," FROM [" . self::TABLE_TC_VEHICLE . "] WHERE unit_id=%i", $unitId, " AND deleted=0");
    }
    
    public function add($data) {
        return dibi::insert(self::TABLE_TC_VEHICLE, $data)->execute();
    }
    
    
//    
//    public function delete($travelId) {
//        return dibi::query("DELETE FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id = %i", $travelId, "LIMIT 1");
//    }

}