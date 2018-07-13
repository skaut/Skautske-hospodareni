<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\LockCashbookHandler;

/**
 * @see LockCashbookHandler
 */
final class LockCashbook
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $userId;

    public function __construct(CashbookId $cashbookId, int $userId)
    {
        $this->cashbookId = $cashbookId;
        $this->userId     = $userId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }
}
