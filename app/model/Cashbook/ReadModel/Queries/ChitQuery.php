<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\ReadModel\QueryHandlers\ChitQueryHandler;

/**
 * @see ChitQueryHandler
 */
final class ChitQuery
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    public function __construct(CashbookId $cashbookId, int $chitId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId     = $chitId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }
}
