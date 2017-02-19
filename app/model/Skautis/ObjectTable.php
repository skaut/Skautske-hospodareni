<?php

namespace Model\Skautis;

use Dibi\Connection;
use Model\BaseTable;

class ObjectTable
{

    private const TABLE = BaseTable::TABLE_OBJECT;

    /** @var Connection */
    private $connection;

    /**
     * ObjectTable constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(int $skautisId, string $type): int
    {
        return $this->connection->insert(self::TABLE, [
            'skautisId' => $skautisId,
            'type' => $type,
        ])->execute(\dibi::IDENTIFIER);
    }

    /**
     * Vyhleda akci|jednotku a pokud tam není, tak založí její záznam
     * @param int $skautisEventId
     * @param  $type
     * @return int
     */
    public function getLocalId($skautisEventId, $type): ?int
    {
        return $this->connection->select('id')
            ->from(self::TABLE)
            ->where('skautisId = %i', $skautisEventId)
            ->where('type = %s', $type)
            ->fetchSingle();
    }

    public function getSkautisId(int $localId, string $type): ?int
    {
        return $this->connection->select('skautisId')
            ->from(self::TABLE)
            ->where('id = %i', $localId)
            ->where('type = %s', $type)
            ->fetchSingle();
    }

}
