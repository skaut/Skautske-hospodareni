<?php

namespace Model;

/**
 * @author Sinacek
 * správa cest na cestovních příkazech
 */
class TravelTable extends BaseTable {

    public function get($travelId) {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id=%i", $travelId, " LIMIT 1");
    }
    
    public function getAll($commandId) {
        return $this->connection->fetchAll("SELECT * FROM [" . self::TABLE_TC_TRAVELS . "] WHERE command_id=%i", $commandId, " ORDER BY start_date, id asc");
    }
    
    public function add($data) {
        return $this->connection->insert(self::TABLE_TC_TRAVELS, $data)->execute();
    }
    
    public function update($data, $tId) {
        return $this->connection->update(self::TABLE_TC_TRAVELS, $data)->where("id=%i", $tId)->limit(1)->execute();
    }
    
    public function delete($travelId) {
        return $this->connection->query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id = %i", $travelId, "LIMIT 1");
    }
    
    public function deleteAll($commandId) {
        return $this->connection->query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE command_id = %i", $commandId);
    }


}