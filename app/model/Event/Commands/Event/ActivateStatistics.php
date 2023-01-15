<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\ActivateStatisticsHandler;

/** @see ActivateStatisticsHandler */
final class ActivateStatistics
{
    public function __construct(private int $eventId)
    {
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }
}
