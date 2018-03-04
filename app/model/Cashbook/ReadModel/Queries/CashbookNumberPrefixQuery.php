<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\CashbookNumberPrefixQueryHandler;

/**
 * @see CashbookNumberPrefixQueryHandler
 */
class CashbookNumberPrefixQuery
{

    /** @var int */
    private $cashbookId;

    public function __construct(int $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

}
