<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class EventsWithoutGroupQuery
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
