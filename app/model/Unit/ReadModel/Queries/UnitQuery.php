<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\Queries;

use Model\Unit\ReadModel\QueryHandlers\UnitQueryHandler;

/** @see UnitQueryHandler */
final class UnitQuery
{
    public function __construct(private int $unitId)
    {
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }
}
