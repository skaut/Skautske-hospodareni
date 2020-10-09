<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\ReadModel\QueryHandlers\EducationListQueryHandler;

/**
 * @see EducationListQueryHandler
 */
final class EducationListQuery
{
    private ?int $year;

    public function __construct(?int $year)
    {
        $this->year = $year;
    }

    public function getYear() : ?int
    {
        return $this->year;
    }
}
