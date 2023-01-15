<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Event\SkautisEventId;

/** @see EventCashbookIdQueryHandler */
final class EventCashbookIdQuery
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
