<?php

declare(strict_types=1);

namespace Model;

use Dibi\Exception;
use function array_fill;
use function count;

class CommandTable extends BaseTable
{
    /**
     * @param int[]|string[] $commandTypes
     * @throws Exception
     */
    public function updateTypes(int $commandId, array $commandTypes) : void
    {
        $this->connection->query('DELETE FROM [' . self::TABLE_TC_COMMAND_TYPES . '] WHERE commandId=%i', $commandId);
        $toInsert = [
            'commandId' => array_fill(0, count($commandTypes), $commandId),
            'typeId' => $commandTypes,
        ];
        $this->connection->query('INSERT INTO [' . self::TABLE_TC_COMMAND_TYPES . '] %m', $toInsert);
    }
}
