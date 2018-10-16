<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/**
 * @see CampListQueryHandler
 */
final class CampListQuery
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
