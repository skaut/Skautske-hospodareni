<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Common\UnitId;

/** @see ActiveUnitCashbookQueryHandler */
final class ActiveUnitCashbookQuery
{
    public function __construct(private UnitId $unitId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }
}
