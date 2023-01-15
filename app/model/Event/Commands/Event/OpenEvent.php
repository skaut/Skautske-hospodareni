<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\OpenEventHandler;
use Model\Event\SkautisEventId;

/** @see OpenEventHandler */
final class OpenEvent
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
