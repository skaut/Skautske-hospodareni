<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/** @see CampListQueryHandler */
final class CampListQuery
{
    public function __construct(
        private ?int $year,
        private ?string $state = null,
    ) {
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
