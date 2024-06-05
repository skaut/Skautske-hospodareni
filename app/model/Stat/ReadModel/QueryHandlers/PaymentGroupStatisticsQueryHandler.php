<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Model\Event\ReadModel\Queries\PaymentGroupStatisticsQuery;

class PaymentGroupStatisticsQueryHandler
{
    private Connection $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    /** @return float[] */
    public function __invoke(PaymentGroupStatisticsQuery $query): array
    {
        $params = [
            $query->getUnitIds(),
            $query->getYear(),
        ];

        $types = [Connection::PARAM_INT_ARRAY, ParameterType::INTEGER];
        $sql   = <<<'SQL'
            SELECT p.unit_id, COUNT(p.id) as count
            FROM `pa_group_unit` p
            LEFT JOIN `pa_group` g ON g.id = p.group_id
            WHERE p.unit_id IN (?) AND DATE_FORMAT(g.created_at, '%Y') = ? 
            GROUP BY p.unit_id
SQL;

        $stmt = $this->db->executeQuery($sql, $params, $types);

        return $stmt->fetchAllKeyValue();
    }
}
