<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Event;

use App\Model\Event\Handlers\Event\OpenEventHandler;
use App\Model\Event\SkautisEventId;

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
