<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\EventParticipantListQueryHandler;
use App\Model\Event\SkautisEventId;

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
