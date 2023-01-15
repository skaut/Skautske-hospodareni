<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/** @see CampListQueryHandler */
final class CampListQuery
{
    public function __construct(private int|null $year = null, private string|null $state = null)
    {
    }

    public function getYear(): int|null
    {
        return $this->year;
    }

    public function getState(): string|null
    {
        return $this->state;
    }
}
