<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\UnitCashbookListQueryHandler;
use Model\Common\UnitId;

/** @see UnitCashbookListQueryHandler */
final class UnitCashbookListQuery
{
    public function __construct(private UnitId $unitId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
