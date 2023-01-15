<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\Queries;

use Model\Unit\ReadModel\QueryHandlers\UnitsDetailQueryHandler;

/** @see UnitsDetailQueryHandler */
final class UnitsDetailQuery
{
    /** @param int[] $unitIds */
    public function __construct(private array $unitIds)
    {
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }
}
