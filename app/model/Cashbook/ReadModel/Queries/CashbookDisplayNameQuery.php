<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;

final class CashbookDisplayNameQuery
{
    public function __construct(private CashbookId $cashbookId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
