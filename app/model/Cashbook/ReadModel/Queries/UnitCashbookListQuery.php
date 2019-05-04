<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\UnitCashbookListQueryHandler;
use Model\Common\UnitId;

/**
 * @see UnitCashbookListQueryHandler
 */
final class UnitCashbookListQuery
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
