<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\EventParticipantListQueryHandler;
use Model\Event\SkautisEventId;

/** @see EventParticipantListQueryHandler */
final class EventParticipantListQuery
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
