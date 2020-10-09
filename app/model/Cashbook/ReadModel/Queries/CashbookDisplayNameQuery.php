<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;

final class CashbookDisplayNameQuery
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
