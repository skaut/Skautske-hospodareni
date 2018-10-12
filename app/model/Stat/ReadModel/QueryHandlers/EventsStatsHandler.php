<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\QueryHandlers;

use Doctrine\DBAL\Connection;
use Model\Event\ReadModel\Queries\EventsStats;
use function array_map;

class EventsStatsHandler
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
    public function handle(EventsStats $query) : array
    {
        $params = [$query->getEventIds()];
        $types  = [Connection::PARAM_INT_ARRAY];
        $sql    = 'SELECT eventId, SUM(price) as sum ' .
            'FROM `ac_chits` ' .
            'WHERE eventId IN (?) AND category_operation_type = \'out\' ';
        if ($query->getYear() !== null) {
            $sql     .= 'AND YEAR(date) = ? ';
            $params[] = $query->getYear();
            $types[]  = 'integer';
        }
        $sql .= 'GROUP BY eventId';

        $stmt = $this->db->executeQuery($sql, $params, $types);

        return array_map('floatval', $stmt->fetchAll(\PDO::FETCH_KEY_PAIR));
    }
}
