<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa smluv o proplácení cestovních náhrad u cestovních příkazů
 */
class ContractTable extends BaseTable {

    public function get($id) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "] WHERE id=%i AND deleted=0", $id, " LIMIT 1");
    }
    
    public function getAll($unitId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "] WHERE unit_id=%i AND deleted=0", $unitId, " ORDER BY start DESC");
    }
    
    public function add($values) {
        return $this->connection->query("INSERT INTO [". self::TABLE_TC_CONTRACTS."] ", $values);
    }
    
    public function delete($contractId) {
        return $this->connection->query("UPDATE [" . self::TABLE_TC_CONTRACTS . "] SET deleted=1 WHERE id = %i AND deleted=0 LIMIT 1", $contractId);
    }

}