<?php

declare(strict_types=1);

namespace Model\Event\Commands;

use Model\Event\Handlers\Event\CancelEventHandler;
use Model\Event\SkautisEventId;

/** @see CancelEventHandler */
final class CancelEvent
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
