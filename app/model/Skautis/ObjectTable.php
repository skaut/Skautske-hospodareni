<?php

declare(strict_types=1);

namespace Model\Skautis;

use Doctrine\DBAL\Connection;
use Model\Cashbook\Cashbook\CashbookId;

class ObjectTable
{
    private const TABLE = 'ac_object';

    private Connection $connection;

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
        ]);
    }

    /**
     * Vyhleda akci|jednotku
     */
    public function getLocalId(int $skautisEventId, string $type) : ?CashbookId
    {
        $row = $this->connection->executeQuery(
            'SELECT id FROM ' . self::TABLE . ' WHERE skautisId = :skautisId AND type = :type',
            [
                'skautisId' => $skautisEventId,
                'type' => $type,
            ]
        )->fetch();

        return $row !== false ? CashbookId::fromString($row['id']) : null;
    }

    public function getSkautisId(CashbookId $cashbookId, string $type) : ?int
    {
        $row = $this->connection->executeQuery(
            'SELECT skautisId FROM ' . self::TABLE . ' WHERE id = :id AND type = :type',
            [
                'id' => $cashbookId->toString(),
                'type' => $type,
            ]
        )->fetch();

        return $row !== false ? (int) $row['skautisId'] : null;
    }
}
