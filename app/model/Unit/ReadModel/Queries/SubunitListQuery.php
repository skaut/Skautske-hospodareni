<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\Queries;

use Model\Common\UnitId;
use Model\Unit\ReadModel\QueryHandlers\SubunitListQueryHandler;

/**
 * @see SubunitListQueryHandler
 */
final class SubunitListQuery
{
    private UnitId $unitId;

    public function __construct(UnitId $unitId)
    {
        $this->unitId = $unitId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }
}
