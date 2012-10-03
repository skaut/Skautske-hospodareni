<?php

/**
 * @author Hána František
 */
class VehicleTable extends BaseTable {
    
    const LABEL = "CONCAT(type,' (', spz,')')";

    public function get($vehicleId, $withDeleted = false) {
        return dibi::fetch("SELECT *, ", self::LABEL," as label FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id=%i", $vehicleId, "%if", !$withDeleted," AND deleted=0 %end", " LIMIT 1");
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
    
    public function remove($vehicleId) {
        return dibi::update(self::TABLE_TC_VEHICLE, array("deleted"=>1))->where("id=%i", $vehicleId)->execute();
    }
    
    
//    
//    public function delete($travelId) {
//        return dibi::query("DELETE FROM [" . self::TABLE_TC_VEHICLE . "] WHERE id = %i", $travelId, "LIMIT 1");
//    }

}