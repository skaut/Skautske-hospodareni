<?php

declare(strict_types=1);

namespace App\Model\Event\Commands;

use App\Model\Event\Handlers\Event\CancelEventHandler;
use App\Model\Event\SkautisEventId;

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
