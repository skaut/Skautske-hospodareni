<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Operation;
use Model\Cashbook\ReadModel\QueryHandlers\CategoryPairsQueryHandler;

/**
 * @see CategoryPairsQueryHandler
 */
final class CategoryPairsQuery
{

    /** @var int */
    private $cashbookId;

    /** @var Operation|NULL */
    private $operationType;

    public function __construct(int $cashbookId, ?Operation $operationType = NULL)
    {
        $this->cashbookId = $cashbookId;
        $this->operationType = $operationType;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getOperationType(): ?Operation
    {
        return $this->operationType;
    }

}
