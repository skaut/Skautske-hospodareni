<?php

declare(strict_types=1);

namespace App\Model\Skautis\ReadModel\Queries;

use App\Model\Event\SkautisEventId;
use App\Model\Skautis\ReadModel\QueryHandlers\EventStatisticsQueryHandler;

/** @see EventStatisticsQueryHandler */
final class EventStatisticsQuery
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
