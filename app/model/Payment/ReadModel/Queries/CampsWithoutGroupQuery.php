<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\CampsWithoutGroupQueryHandler;

/**
 * @see CampsWithoutGroupQueryHandler
 */
final class CampsWithoutGroupQuery
{
    private int $year;

    public function __construct(int $year)
    {
        $this->year = $year;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
