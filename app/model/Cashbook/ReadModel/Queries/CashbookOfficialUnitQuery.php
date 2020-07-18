<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;

/**
 * @see CashbookOfficialUnitQueryHandler
 */
final class CashbookOfficialUnitQuery
{
    private CashbookId $cashbookId;

    public function __construct(CashbookId $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }
}
