<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Operation;
use App\Model\Cashbook\ReadModel\QueryHandlers\CategoryPairsQueryHandler;

/** @see CategoryPairsQueryHandler */
final class CategoryPairsQuery
{
    public function __construct(private CashbookId $cashbookId, private ?Operation $operationType = null)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getOperationType(): ?Operation
    {
        return $this->operationType;
    }
}
