<?php

declare(strict_types=1);

namespace Model\Skautis\ReadModel\Queries;

use Model\Event\SkautisEventId;
use Model\Skautis\ReadModel\QueryHandlers\EventStatisticsQueryHandler;

/**
 * @see EventStatisticsQueryHandler
 */
final class EventStatisticsQuery
{
    private SkautisEventId $eventId;

    public function __construct(SkautisEventId $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }
}
