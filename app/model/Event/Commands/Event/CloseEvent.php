<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\CloseEventHandler;

/**
 * @see CloseEventHandler
 */
final class CloseEvent
{
    /** @var int */
    private $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId() : int
    {
        return $this->eventId;
    }
}
