<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\UnitCashbookListQueryHandler;

/**
 * @see UnitCashbookListQueryHandler
 */
final class UnitCashbookListQuery
{

    /** @var int */
    private $unitId;

    public function __construct(int $unitId)
    {
        $this->unitId = $unitId;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

}
