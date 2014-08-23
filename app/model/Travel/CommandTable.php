<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa cestovních příkazů
 */
class CommandTable extends BaseTable {

    /**
     * vrací konkretní příkaz
     * @param type $commandId - id příkazu
     * @return DibiRow 
     */
    public function get($commandId) {
        return $this->connection->fetch("SELECT com.*, c.type as vehicle_type, c.spz as vehicle_spz, c.consumption as vehicle_consumption
            FROM [" . self::TABLE_TC_COMMANDS . "] as com
            LEFT JOIN [" . self::TABLE_TC_VEHICLE . "] as c ON (com.vehicle_id = c.id) WHERE com.id=%i AND com.deleted=0", $commandId);
    }

    public function add($v) {
        return $this->connection->query("INSERT INTO [" . self::TABLE_TC_COMMANDS . "] ", $v);
    }

    public function update($v, $id) {
        return $this->connection->query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET", $v, "WHERE id=%s", $id);
    }

    public function getAll($unitId, $returnQuery = FALSE) {
        $q = $this->connection->select("com.*, con.unit_id as unitId, con.driver_name, c.type as vehicle_type, c.spz as vehicle_spz")
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
        return $this->getAll($unitId, TRUE)
                        ->where("com.contract_id=", $contractId)
                        ->fetchAll();
    }

    public function getAllByVehicle($unitId, $vehicleId) {
        return $this->getAll($unitId, TRUE)
                        ->where("com.vehicle_id=", $vehicleId)
                        ->fetchAll();
    }

    /**
     * uzavírání/otevírání cestovních příkazů
     * @param type $commandId
     * @param type $state
     * @return type
     */
    public function changeState($commandId, $state) {
        return $this->connection->update(self::TABLE_TC_COMMANDS, array("id" => $commandId, "closed" => $state))
                        ->where("id=%i", $commandId)
                        ->where("deleted = 0")
                        ->execute();
    }

    public function delete($commandId) {
        return $this->connection->query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET deleted=1 WHERE id = %i AND deleted=0 LIMIT 1", $commandId);
    }

}