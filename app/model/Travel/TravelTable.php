<?php

namespace Model;

class TravelTable extends BaseTable
{

    public function getTypes($pairs = FALSE)
    {
        if ($pairs) {
            return $this->connection->fetchPairs("SELECT type, label FROM [" . self::TABLE_TC_TRAVEL_TYPES . "] ORDER BY [order] DESC");
        }

        $types = $this->connection->fetchAll('SELECT type, label, hasFuel FROM [' . self::TABLE_TC_TRAVEL_TYPES . '] ORDER BY [order] DESC');
        $result = [];

        foreach ($types as $type) {
            $result[$type->type] = $type;
        }

        return $result;
    }


}
