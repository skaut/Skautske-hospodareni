<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/** @see CampStatsQueryHandler */
final class CampStatsQuery
{
    public function __construct(private readonly int|null $year = null)
    {
    }

    public function getYear(): int|null
    {
        return $this->year;
    }
}
