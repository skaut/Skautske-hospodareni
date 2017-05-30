<?php

namespace Model;

/**
 * @author Hána František <sinacek@gmail.com>
 * správa cest na cestovních příkazech
 */
class TravelTable extends BaseTable
{

    public function getTypes($pairs = FALSE)
    {
        if ($pairs) {
            return $this->connection->fetchPairs("SELECT type, label FROM [" . self::TABLE_TC_TRAVEL_TYPES . "] ORDER BY [order] DESC");
        }
        return $this->connection->query("SELECT type, label, hasFuel FROM [" . self::TABLE_TC_TRAVEL_TYPES . "] ORDER BY [order] DESC")->fetchAssoc("type");
    }


}
