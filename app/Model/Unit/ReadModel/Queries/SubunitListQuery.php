<?php

declare(strict_types=1);

namespace App\Model\Unit\ReadModel\Queries;

use App\Model\Common\UnitId;
use App\Model\Unit\ReadModel\QueryHandlers\SubunitListQueryHandler;

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
