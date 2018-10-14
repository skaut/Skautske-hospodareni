<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\CloseEventHandler;
use Model\Event\SkautisEventId;

/**
 * @see CloseEventHandler
 */
final class CloseEvent
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
