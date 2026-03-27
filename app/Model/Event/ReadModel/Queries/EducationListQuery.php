<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\ReadModel\QueryHandlers\EducationListQueryHandler;

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
