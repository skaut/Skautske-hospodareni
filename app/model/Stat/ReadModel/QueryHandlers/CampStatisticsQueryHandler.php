<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Operation;
use Model\Event\ReadModel\Queries\CampStatisticsQuery;
use Model\Event\SkautisCampId;
use PDO;
use function array_map;

class CampStatisticsQueryHandler
{
    private Connection $db;

    public function __construct(Connection $connection)
    {
        $this->db = $connection;
    }

    /**
     * @return float[]
     */
    public function __invoke(CampStatisticsQuery $query) : array
    {
        $params = [
            array_map(function (SkautisCampId $id) {
                return $id->toInt();
            }, $query->getCampIds()),
            ObjectType::CAMP,
            Operation::EXPENSE,
            $query->getYear(),
        ];
        $types  = [Connection::PARAM_INT_ARRAY, ParameterType::INTEGER];
        $sql    = <<<'SQL'
            SELECT o.skautisId, SUM(ci.price) as sum
            FROM `ac_chits` c
            LEFT JOIN `ac_chit_to_item` cti ON c.id = cti.chit_id
            LEFT JOIN `ac_chits_item` ci ON cti.item_id = ci.id
            JOIN ac_object o ON c.eventId = o.id
            WHERE o.skautisId IN (?) AND o.type = ? AND category_operation_type = ?
            AND YEAR(date) = ?
            GROUP BY eventId
SQL;

        $stmt = $this->db->executeQuery($sql, $params, $types);

        return array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }
}
