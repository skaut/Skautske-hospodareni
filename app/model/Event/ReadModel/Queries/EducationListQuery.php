<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EducationListQueryHandler;

/** @see EducationListQueryHandler */
final class EducationListQuery
{
    public function __construct(private ?int $year)
    {
    }

    public function getYear(): ?int
    {
        return $this->year;
    }
}
