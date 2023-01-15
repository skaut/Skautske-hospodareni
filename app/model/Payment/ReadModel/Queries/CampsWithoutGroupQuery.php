<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\CampsWithoutGroupQueryHandler;

/** @see CampsWithoutGroupQueryHandler */
final class CampsWithoutGroupQuery
{
    public function __construct(private int $year)
    {
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
