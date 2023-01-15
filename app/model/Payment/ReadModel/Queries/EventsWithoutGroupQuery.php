<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class EventsWithoutGroupQuery
{
    public function __construct(private int $year)
    {
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
