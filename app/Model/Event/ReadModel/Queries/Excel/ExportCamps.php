<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries\Excel;

use App\Model\Event\ReadModel\QueryHandlers\Excel\ExportCampsHandler;

/** @see ExportCampsHandler */
final class ExportCamps
{
    /** @var int[] */
    private array $campIds;

    /** @param int[] $eventIds */
    public function __construct(array $eventIds)
    {
        $this->campIds = $eventIds;
    }

    /** @return int[] */
    public function getCampIds(): array
    {
        return $this->campIds;
    }
}
