<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\EducationsWithoutGroupQueryHandler;

/** @see EducationsWithoutGroupQueryHandler */
final class EducationsWithoutGroupQuery
{
    public function __construct(private int $year)
    {
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
