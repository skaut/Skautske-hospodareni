<?php

declare(strict_types=1);

namespace Model;

use function array_fill;
use function count;

class CommandTable extends BaseTable
{
    /**
     * @param mixed[] $commandIds
     * @return mixed[]
     */
    public function getTypes(array $commandIds) : array
    {
        return $this->connection->select("com.id as comId, (SELECT GROUP_CONCAT(tt.label SEPARATOR ', ') FROM " . self::TABLE_TC_COMMAND_TYPES . ' ct LEFT JOIN ' . self::TABLE_TC_TRAVEL_TYPES . ' tt ON (ct.typeId = tt.type) WHERE commandId = com.id) as types')
            ->from(self::TABLE_TC_COMMANDS . ' AS com')
            ->where('com.id IN %in', $commandIds)
            ->fetchPairs('comId', 'types');
    }

    public function updateTypes(int $commandId, array $commandTypes) : void
    {
        $this->connection->query('DELETE FROM [' . self::TABLE_TC_COMMAND_TYPES . '] WHERE commandId=%i', $commandId);
        $toInsert = [
            'commandId' => array_fill(0, count($commandTypes), $commandId),
            'typeId' => $commandTypes,
        ];
        $this->connection->query('INSERT INTO [' . self::TABLE_TC_COMMAND_TYPES . '] %m', $toInsert);
    }

    /**
     * @return mixed[]
     */
    public function getCommandTypes(int $commandId) : array
    {
        return $this->connection->fetchPairs('SELECT tt.type, tt.label FROM [' . self::TABLE_TC_COMMAND_TYPES . '] ct LEFT JOIN [' . self::TABLE_TC_TRAVEL_TYPES . '] tt ON (ct.typeId = tt.type) WHERE ct.commandId=%i', $commandId);
    }
}
