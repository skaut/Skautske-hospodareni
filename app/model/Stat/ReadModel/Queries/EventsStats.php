<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/**
 * @see EventsStatsHandler
 */
final class EventsStats
{
    /** @var int[] */
    private $eventIds;

    /** @var int|null */
    private $year;

    /**
     * @param int[] $eventIds
     */
    public function __construct(array $eventIds, ?int $year)
    {
        $this->eventIds = $eventIds;
        $this->year     = $year;
    }

    /**
     * @return int[]
     */
    public function getEventIds() : array
    {
        return $this->eventIds;
    }

    public function getYear() : ?int
    {
        return $this->year;
    }
}
