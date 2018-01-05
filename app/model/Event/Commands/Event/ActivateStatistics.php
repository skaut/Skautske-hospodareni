<?php

namespace Model\Event\Commands\Event;

use Model\Event\Handlers\Event\ActivateStatisticsHandler;

/**
 * @see ActivateStatisticsHandler
 */
final class ActivateStatistics
{

    /** @var int */
    private $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

}
