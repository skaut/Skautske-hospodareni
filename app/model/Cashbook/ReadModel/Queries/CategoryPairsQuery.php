<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\QueryHandlers\CategoryPairsQueryHandler;

/** @see CategoryPairsQueryHandler */
final class CategoryPairsQuery
{
    public function __construct(private CashbookId $cashbookId, private Operation|null $operationType = null)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getOperationType(): Operation|null
    {
        return $this->operationType;
    }
}
