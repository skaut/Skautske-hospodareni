<?php

declare(strict_types=1);

namespace Model\Events\Events;

use Model\Event\SkautisEventId;

final class EventWasCanceled
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
