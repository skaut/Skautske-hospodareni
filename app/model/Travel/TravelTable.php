<?php

declare(strict_types=1);

namespace Model;

use Dibi\Exception;
use Dibi\Row;
use function array_column;
use function array_combine;

class TravelTable extends BaseTable
{
    /**
     * @return Row[] Types indexed by ID
     * @throws Exception
     */
    public function getTypes(bool $pairs = false) : array
    {
        if ($pairs) {
            return $this->connection->fetchPairs('SELECT type, label FROM [' .
                self::TABLE_TC_TRAVEL_TYPES . '] ORDER BY [order] DESC');
        }

        $types = $this->connection->fetchAll('SELECT type, label, hasFuel FROM ['
            . self::TABLE_TC_TRAVEL_TYPES . '] ORDER BY [order] DESC');

        return array_combine(array_column($types, 'type'), $types);
    }
}
