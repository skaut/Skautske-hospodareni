<?php

declare(strict_types=1);

namespace Model\Event\Commands;

use Model\Event\Handlers\Event\CancelEventHandler;
use Model\Event\SkautisEventId;

/**
 * @see CancelEventHandler
 */
final class CancelEvent
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
