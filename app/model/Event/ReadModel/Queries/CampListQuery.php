<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

/**
 * @see CampListQueryHandler
 */
final class CampListQuery
{
    /** @var int|null */
    private $year;

    /** @var string|null */
    private $state;

    public function __construct(?int $year, ?string $state = null)
    {
        $this->year  = $year;
        $this->state = $state;
    }

    public function getYear() : ?int
    {
        return $this->year;
    }

    public function getState() : ?string
    {
        return $this->state;
    }
}
