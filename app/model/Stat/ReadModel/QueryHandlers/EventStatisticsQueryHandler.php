<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Model\Cashbook\Operation;
use Model\Event\ReadModel\Queries\EventStatisticsQuery;
use Model\Event\SkautisEventId;

use function array_map;

class EventStatisticsQueryHandler
{
    private Connection $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    /** @return float[] */
    public function __invoke(EventStatisticsQuery $query): array
    {
        $params = [
            array_map(function (SkautisEventId $id) {
                return $id->toInt();
            }, $query->getEventIds()),
            Operation::EXPENSE,
            $query->getYear(),
        ];
        $types  = [Connection::PARAM_INT_ARRAY, ParameterType::INTEGER];
        $sql    = <<<'SQL'
            SELECT o.id, COUNT(ci.price) as sum
            FROM `ac_chits` c
            LEFT JOIN `ac_chit_to_item` cti ON c.id = cti.chit_id
            LEFT JOIN `ac_chits_item` ci ON cti.item_id = ci.id
            JOIN ac_event_cashbooks o ON c.eventId = o.cashbook_id
            WHERE o.id IN (?) AND category_operation_type = ? AND YEAR(date) = ?
            GROUP BY o.id
SQL;

        $stmt = $this->db->executeQuery($sql, $params, $types);

        return $stmt->fetchAllKeyValue();
    }
}
