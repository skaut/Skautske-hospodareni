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
        return dibi::fetch("SELECT * FROM [" . self::TABLE_TC_COMMANDS . "] WHERE id=%i AND deleted=0", $commandId);
    }

    public function add($v){
        return dibi::query("INSERT INTO [". self::TABLE_TC_COMMANDS."] ", $v);
    }
    
    public function update($v, $id){
        return dibi::query("UPDATE [". self::TABLE_TC_COMMANDS."] SET", $v, "WHERE id=%s", $id);
    }


    public function getAll($unitId, $commandId) {
        return dibi::fetchAll("SELECT com.*, con.unit_id as unitId, con.driver_name FROM [" . self::TABLE_TC_COMMANDS . "] as com 
            LEFT JOIN [" . self::TABLE_TC_CONTRACTS. "] as con ON (com.contract_id = con.id) WHERE con.unit_id=%i AND com.deleted=0 AND con.deleted=0 ", $unitId, 
                "%if", $commandId != NULL, " AND com.contract_id=",  $commandId,"%end");
    }
    public function getAllContracts($unitId) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_TC_CONTRACTS. "]
                WHERE unit_id=%i AND deleted=0 ", $unitId, 
                "ORDER BY id DESC ");
    }
    
    public function delete($commandId) {
        return dibi::query("UPDATE [" . self::TABLE_TC_COMMANDS . "] SET deleted=1 WHERE id = %i AND deleted=0 LIMIT 1", $commandId);
    }


}