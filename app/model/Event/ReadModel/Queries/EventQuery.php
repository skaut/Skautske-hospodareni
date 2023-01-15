<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisEventId;

/** @see EventQueryHandler */
class EventQuery
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
