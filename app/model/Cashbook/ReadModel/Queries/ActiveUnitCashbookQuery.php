<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Common\UnitId;

/**
 * @see ActiveUnitCashbookQueryHandler
 */
final class ActiveUnitCashbookQuery
{
    /** @var UnitId */
    private $unitId;

    public function __construct(UnitId $unitId)
    {
        $this->unitId = $unitId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }
}
