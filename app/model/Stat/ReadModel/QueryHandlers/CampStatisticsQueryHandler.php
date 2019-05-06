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
    /** @var Connection */
    private $db;

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
            $query->getYear(),
        ];
        $types  = [Connection::PARAM_INT_ARRAY, ParameterType::INTEGER];
        $sql    = 'SELECT o.skautisId, SUM(c.price) as sum ' .
            'FROM `ac_chits` c ' .
            'JOIN ac_object o ON c.eventId = o.id ' .
            'WHERE o.skautisId IN (?) AND o.type = \'' . ObjectType::CAMP . '\' AND category_operation_type = \'' . Operation::EXPENSE . '\' ' .
            'AND YEAR(date) = ? ';

        $sql .= 'GROUP BY eventId';

        $stmt = $this->db->executeQuery($sql, $params, $types);

        return array_map('floatval', $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
    }
}
