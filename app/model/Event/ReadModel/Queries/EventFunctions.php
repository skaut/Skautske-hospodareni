<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EventFunctionsQueryHandler;
use Model\Event\SkautisEventId;

/**
 * @see EventFunctionsQueryHandler
 */
final class EventFunctions
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
