<?php

declare(strict_types=1);

namespace Model;

use Dibi\Exception;

class TravelTable extends BaseTable
{
    /**
     * @return mixed[]
     * @throws Exception
     */
    public function getTypes(bool $pairs = false) : array
    {
        if ($pairs) {
            return $this->connection->fetchPairs('SELECT type, label FROM [' .
                self::TABLE_TC_TRAVEL_TYPES . '] ORDER BY [order] DESC');
        }

        $types  = $this->connection->fetchAll('SELECT type, label, hasFuel FROM [' .
            self::TABLE_TC_TRAVEL_TYPES . '] ORDER BY [order] DESC');
        $result = [];

        foreach ($types as $type) {
            $result[$type->type] = $type;
        }

        return $result;
    }
}
