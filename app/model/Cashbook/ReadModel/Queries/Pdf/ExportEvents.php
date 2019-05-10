<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries\Pdf;

/**
 * @see ExportEventsHandler
 */
final class ExportEvents
{
    /** @var int[] */
    private $eventIds;

    /**
     * @param int[] $eventIds
     */
    public function __construct(array $eventIds)
    {
        $this->eventIds = $eventIds;
    }

    /**
     * @return int[]
     */
    public function getEventIds() : array
    {
        return $this->eventIds;
    }
}
