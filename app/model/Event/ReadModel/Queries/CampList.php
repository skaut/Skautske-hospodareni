<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/**
 * @see CampListHandler
 */
final class CampList
{
    /** @var int|null */
    private $year;

    public function __construct(?int $year = null)
    {
        $this->year = $year;
    }

    public function getYear() : ?int
    {
        return $this->year;
    }
}
