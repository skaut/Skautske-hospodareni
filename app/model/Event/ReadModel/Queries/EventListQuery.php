<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/** @see EventListQueryHandler */
final class EventListQuery
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
