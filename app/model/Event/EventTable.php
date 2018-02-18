<?php

namespace Model;

/**
 * @author Hána František
 */
class EventTable extends BaseTable
{

    public function updatePrefix(int $localId, $prefix): bool
    {
        if($prefix == '') {
            $prefix = NULL;
        }

        return (bool) $this->connection->update(self::TABLE_OBJECT, ['prefix' => $prefix])
            ->where('id = ?', $localId)
            ->execute();
    }

    public function getPrefix(int $localId): ?string
    {
        $prefix = $this->connection->select('prefix')
            ->from(self::TABLE_OBJECT)
            ->where('id = ', $localId)
            ->fetchSingle();

        return $prefix != '' ? $prefix : NULL;
    }

}
