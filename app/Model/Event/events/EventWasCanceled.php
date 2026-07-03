<?php

declare(strict_types=1);

namespace App\Model\Events\Events;

use App\Model\Event\SkautisEventId;

final class EventWasCanceled
{
    public function __construct(private SkautisEventId $eventId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }
}
