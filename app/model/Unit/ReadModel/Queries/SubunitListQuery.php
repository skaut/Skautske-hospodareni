<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Unit\ReadModel\QueryHandlers\SubunitListQueryHandler;

/** @see SubunitListQueryHandler */
final class SubunitListQuery
{
    public function __construct(private UnitId $unitId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
