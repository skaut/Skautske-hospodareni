<?php

namespace Model;

use Dibi\Row;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa cestovních příkazů
 */
class CommandTable extends BaseTable
{

    /**
     * vrací konkretní příkaz
     * @param int $commandId - id příkazu
     * @return Row
     */
    public function get($commandId)
    {
        return $this->connection->fetch("SELECT com.*, c.type as vehicle_type, c.registration as vehicle_spz, c.consumption as vehicle_consumption, com.place
            FROM [" . self::TABLE_TC_COMMANDS . "] as com
            LEFT JOIN [" . self::TABLE_TC_VEHICLE . "] as c ON (com.vehicle_id = c.id) WHERE com.id=%i", $commandId);
    }

    public function update($v, $id)
    {
        return $this->connection->query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET", $v, "WHERE id=%s", $id);
    }

    public function getAll($unitId, $returnQuery = FALSE)
    {
        $q = $this->connection->select("com.*, con.unit_id as unitId, con.driver_name, c.type as vehicle_type, c.registration as vehicle_spz, com.place")
            ->select("(SELECT COALESCE(sum(distance), 0) FROM tc_travels WHERE command_id = com.id AND type='auv') * (amortization+(com.fuel_price * (c.consumption/100))) + (SELECT COALESCE(sum(distance), 0) FROM tc_travels WHERE command_id = com.id AND type !='auv') as price")
            ->select("(SELECT MIN(start_date) FROM tc_travels WHERE command_id = com.id) as start_date")
            ->select("(SELECT GROUP_CONCAT(tt.label SEPARATOR ', ') FROM " . self::TABLE_TC_COMMAND_TYPES . " ct LEFT JOIN " . self::TABLE_TC_TRAVEL_TYPES . " tt ON (ct.typeId = tt.type) WHERE commandId = com.id) as types")
            ->from(self::TABLE_TC_COMMANDS . " AS com")
            ->leftJoin(self::TABLE_TC_CONTRACTS . " AS con ON (com.contract_id = con.id)")
            ->leftJoin(self::TABLE_TC_VEHICLE . " AS c ON (com.vehicle_id = c.id) ")
            ->where("com.unit_id=%i", $unitId)
            ->where("(con.deleted=0 OR con.deleted IS NULL)")
            ->orderBy("closed, id desc");
        if ($returnQuery) {
            return $q;
        }
        return $q->fetchAll();
    }

    /**
     * @param int $unitId
     * @param int $contractId
     * @return Row[]
     */
    public function getAllByContract($unitId, $contractId)
    {
        return $this->getAll($unitId, TRUE)
            ->where("com.contract_id=", $contractId)
            ->fetchAll();
    }

    public function getAllByVehicle($unitId, $vehicleId)
    {
        return $this->getAll($unitId, TRUE)
            ->where("com.vehicle_id=", $vehicleId)
            ->fetchAll();
    }

    /**
     * uzavírání/otevírání cestovních příkazů
     * @param int $commandId
     * @param string|NULL $state
     */
    public function changeState($commandId, $state): void
    {
        $this->connection->update(self::TABLE_TC_COMMANDS, ["id" => $commandId, "closed" => $state])
            ->where("id=%i", $commandId)
            ->execute();
    }

    public function updateTypes($commandId, $commandTypes)
    {
        $this->connection->query("DELETE FROM [" . self::TABLE_TC_COMMAND_TYPES . "] WHERE commandId=%i", $commandId);
        $toInsert = [
            "commandId" => array_fill(0, count($commandTypes), $commandId),
            "typeId" => $commandTypes
        ];
        return $this->connection->query("INSERT INTO [" . self::TABLE_TC_COMMAND_TYPES . "] %m", $toInsert);
    }

    public function getCommandTypes($commandId)
    {
        return $this->connection->fetchPairs("SELECT tt.type, tt.label FROM [" . self::TABLE_TC_COMMAND_TYPES . "] ct LEFT JOIN [" . self::TABLE_TC_TRAVEL_TYPES . "] tt ON (ct.typeId = tt.type) WHERE ct.commandId=%i", $commandId);
    }

}
