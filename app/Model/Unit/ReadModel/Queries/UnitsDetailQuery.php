<?php

declare(strict_types=1);

namespace App\Model\Unit\ReadModel\Queries;

use App\Model\Unit\ReadModel\QueryHandlers\UnitsDetailQueryHandler;

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
