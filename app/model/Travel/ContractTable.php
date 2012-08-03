<?php

/**
 * @author Hána František
 */
class ContractTable extends BaseTable {

    public function get($id) {
        return dibi::fetch("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "] WHERE id=%i AND deleted=0", $id, " LIMIT 1");
    }
    
    public function getAll($unitId) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_TC_CONTRACTS . "] WHERE unit_id=%i AND deleted=0", $unitId, " ORDER BY start DESC");
    }
    
    public function add($values) {
        return dibi::query("INSERT INTO [". self::TABLE_TC_CONTRACTS."] ", $values);
    }
    
    public function delete($contractId) {
        return dibi::query("UPDATE [" . self::TABLE_TC_CONTRACTS . "] SET deleted=1 WHERE id = %i AND deleted=0 LIMIT 1", $contractId);
    }

}