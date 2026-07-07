<?php

declare(strict_types=1);

namespace App\Model\Stat\ReadModel\Queries;

use App\Model\Stat\ReadModel\QueryHandlers\LocalUnitStatisticsQueryHandler;

/** @see LocalUnitStatisticsQueryHandler */
final class LocalUnitStatisticsQuery
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds, private int $year)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
