<?php

/**
 * @author Sinacek
 * správa cest na cestovních příkazech
 */
class TravelTable extends BaseTable {

    public function get($travelId) {
        return dibi::fetch("SELECT * FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id=%i", $travelId, " LIMIT 1");
    }
    
    public function getAll($commandId) {
        return dibi::fetchAll("SELECT * FROM [" . self::TABLE_TC_TRAVELS . "] WHERE command_id=%i", $commandId, " ORDER BY start_date, id asc");
    }
    
    public function add($data) {
        return dibi::insert(self::TABLE_TC_TRAVELS, $data)->execute();
    }
    
    public function update($data, $tId) {
        return dibi::update(self::TABLE_TC_TRAVELS, $data)->where("id=%i", $tId)->limit(1)->execute();
    }
    
    public function delete($travelId) {
        return dibi::query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id = %i", $travelId, "LIMIT 1");
    }
    
    public function deleteAll($commandId) {
        return dibi::query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE command_id = %i", $commandId);
    }


}