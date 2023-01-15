<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\CloseEventHandler;
use Model\Event\SkautisEventId;

/** @see CloseEventHandler */
final class CloseEvent
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
