<?php

declare(strict_types=1);

namespace Model\Skautis;

use Dibi\Connection;
use Model\BaseTable;
use Model\Cashbook\Cashbook\CashbookId;

class ObjectTable
{
    private const TABLE = BaseTable::TABLE_OBJECT;

    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function add(int $skautisId, CashbookId $cashbookId, string $type) : void
    {
        $this->connection->insert(self::TABLE, [
            'id' => $cashbookId->toString(),
            'skautisId' => $skautisId,
            'type' => $type,
        ])->execute();
    }

    /**
     * Vyhleda akci|jednotku
     */
    public function getLocalId(int $skautisEventId, string $type) : ?CashbookId
    {
        $id = $this->connection->select('id')
            ->from(self::TABLE)
            ->where('skautisId = %i', $skautisEventId)
            ->where('type = %s', $type)
            ->fetchSingle();

        return $id !== false ? CashbookId::fromString($id) : null;
    }

    public function getSkautisId(CashbookId $cashbookId, string $type) : ?int
    {
        $id = $this->connection->select('skautisId')
            ->from(self::TABLE)
            ->where('id = %s', $cashbookId->toString())
            ->where('type = %s', $type)
            ->fetchSingle();

        return $id !== false ? $id : null;
    }
}
