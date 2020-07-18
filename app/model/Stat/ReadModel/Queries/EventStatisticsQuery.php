<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisEventId;
use function array_map;

/**
 * @see EventStatisticsQueryHandler
 */
final class EventStatisticsQuery
{
    /** @var SkautisEventId[] */
    private array $eventIds;

    private int $year;

    /**
     * @param int[] $eventIds
     */
    public function __construct(array $eventIds, int $year)
    {
        $this->eventIds = array_map(function (int $id) {
            return new SkautisEventId($id);
        }, $eventIds);
        $this->year     = $year;
    }

    /**
     * @return SkautisEventId[]
     */
    public function getEventIds() : array
    {
        return $this->eventIds;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
