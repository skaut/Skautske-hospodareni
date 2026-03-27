<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\UnitCashbookListQueryHandler;
use App\Model\Common\UnitId;

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
