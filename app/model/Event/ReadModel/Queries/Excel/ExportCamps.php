<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries\Excel;

use Model\Event\ReadModel\QueryHandlers\Excel\ExportCampsHandler;

/**
 * @see ExportCampsHandler
 */
final class ExportCamps
{
    /** @var int[] */
    private $campIds;

    /**
     * @param int[] $eventIds
     */
    public function __construct(array $eventIds)
    {
        $this->campIds = $eventIds;
    }

    /**
     * @return int[]
     */
    public function getCampIds() : array
    {
        return $this->campIds;
    }
}
