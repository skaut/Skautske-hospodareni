<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa cest na cestovních příkazech
 */
class TravelTable extends BaseTable
{

    public function get($travelId)
    {
        return $this->connection->fetch("SELECT * FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id=%i", $travelId, " LIMIT 1");
    }

    public function add($data)
    {
        return $this->connection->insert(self::TABLE_TC_TRAVELS, $data)->execute(\dibi::IDENTIFIER);
    }

    public function update($data, $tId)
    {
        return $this->connection->update(self::TABLE_TC_TRAVELS, $data)->where("id=%i", $tId)->limit(1)->execute();
    }

    public function delete($travelId)
    {
        return $this->connection->query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE id = %i", $travelId, "LIMIT 1");
    }

    public function deleteAll($commandId)
    {
        return $this->connection->query("DELETE FROM [" . self::TABLE_TC_TRAVELS . "] WHERE command_id = %i", $commandId);
    }

    public function getTypes($pairs = FALSE)
    {
        if ($pairs) {
            return $this->connection->fetchPairs("SELECT type, label FROM [" . self::TABLE_TC_TRAVEL_TYPES . "] ORDER BY [order] DESC");
        }
        return $this->connection->query("SELECT type, label, hasFuel FROM [" . self::TABLE_TC_TRAVEL_TYPES . "] ORDER BY [order] DESC")->fetchAssoc("type");
    }


}
