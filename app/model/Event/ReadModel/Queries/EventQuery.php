<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisEventId;

/**
 * @see EventQueryHandler
 */
class EventQuery
{
    /** @var SkautisEventId */
    private $eventId;

    public function __construct(SkautisEventId $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId() : SkautisEventId
    {
        return $this->eventId;
    }
}
