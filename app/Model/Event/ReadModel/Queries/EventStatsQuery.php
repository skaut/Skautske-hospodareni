<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

/** @see EventStatsQueryHandler */
final class EventStatsQuery
{
    public function __construct(private readonly ?int $year = null)
    {
    }

    public function getYear(): ?int
    {
        return $this->year;
    }
}
