<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries\Excel;

use Model\Event\ReadModel\QueryHandlers\Excel\ExportEventsHandler;

/** @see ExportEventsHandler */
final class ExportEvents
{
    /** @param int[] $eventIds */
    public function __construct(private array $eventIds)
    {
    }

    /** @return int[] */
    public function getEventIds(): array
    {
        return $this->eventIds;
    }
}
