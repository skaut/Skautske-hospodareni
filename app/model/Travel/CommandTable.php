<?php

/**
 * @author Hána František
 */
class CommandTable extends BaseTable {

    /**
     * vrací konkretní paragon
     * @param type $commandId
     * @return DibiRow 
     */
    public function get($commandId) {
        return dibi::fetch("SELECT com.*, c.type as vehicle_type, c.spz as vehicle_spz, c.consumption as vehicle_consumption
            FROM [" . self::TABLE_TC_COMMANDS . "] as com
            LEFT JOIN [" . self::TABLE_TC_VEHICLE . "] as c ON (com.vehicle_id = c.id) WHERE com.id=%i AND com.deleted=0", $commandId);
    }

    public function add($v) {
        return dibi::query("INSERT INTO [" . self::TABLE_TC_COMMANDS . "] ", $v);
    }

    public function update($v, $id) {
        return dibi::query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET", $v, "WHERE id=%s", $id);
    }

    public function getAll($unitId, $returnQuery = FALSE) {
        $q = dibi::select("com.*, con.unit_id as unitId, con.driver_name, c.type as vehicle_type, c.spz as vehicle_spz")
                ->from(self::TABLE_TC_COMMANDS . " AS com")
                ->leftJoin(self::TABLE_TC_CONTRACTS . " AS con ON (com.contract_id = con.id)")
                ->leftJoin(self::TABLE_TC_VEHICLE . " AS c ON (com.vehicle_id = c.id) ")
                ->where("con.unit_id=%i", $unitId)
                ->where("com.deleted=0")
                ->where("con.deleted=0")
                ->orderBy("closed, id");
        if ($returnQuery)
            return $q;
        return $q->fetchAll();
    }

    public function getAllByContract($unitId, $contractId) {
//        return dibi::fetchAll("SELECT com.*, con.unit_id as unitId, con.driver_name, c.type as vehicle_type, c.spz as vehicle_spz
//            FROM [" . self::TABLE_TC_COMMANDS . "] as com 
//            LEFT JOIN [" . self::TABLE_TC_CONTRACTS . "] as con ON (com.contract_id = con.id)
//                LEFT JOIN [" . self::TABLE_TC_VEHICLE . "] AS c ON (com.vehicle_id = c.id)", "WHERE con.unit_id=%i AND com.deleted=0 AND con.deleted=0 ", $unitId, 
//                "%if", $contractId != NULL, " AND com.contract_id=", $contractId, "%end");
        return $this->getAll($unitId, TRUE)
                ->where("com.contract_id=", $contractId)
                ->fetchAll();
        }

    public function getAllByVehicle($unitId, $vehicleId) {
        return $this->getAll($unitId, TRUE)
                ->where("com.vehicle_id=", $vehicleId)
                ->fetchAll();
    }

    public function getAllContracts($unitId) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "]
                WHERE unit_id=%i AND deleted=0 ", $unitId, "ORDER BY id DESC ");
    }
    
    public function changeState($commandId, $state){
        return dibi::update(self::TABLE_TC_COMMANDS, array("id"=>$commandId, "closed"=>$state))
            ->where("id=%i", $commandId)
            ->where("deleted = 0")
                ->execute();
    }

    public function delete($commandId) {
        return dibi::query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET deleted=1 WHERE id = %i AND deleted=0 LIMIT 1", $commandId);
    }

}