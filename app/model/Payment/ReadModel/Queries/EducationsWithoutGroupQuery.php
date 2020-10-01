<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\EducationsWithoutGroupQueryHandler;

/**
 * @see EducationsWithoutGroupQueryHandler
 */
final class EducationsWithoutGroupQuery
{
    /** @var int */
    private $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
