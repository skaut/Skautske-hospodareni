<?php

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EventFunctionsHandler;
use Model\Event\SkautisEventId;

/**
 * @see EventFunctionsHandler
 */
final class EventFunctions
{

    /** @var SkautisEventId */
    private $eventId;

    public function __construct(SkautisEventId $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }

}
