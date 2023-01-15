<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\LockCashbookHandler;

/** @see LockCashbookHandler */
final class LockCashbook
{
    public function __construct(private CashbookId $cashbookId, private int $userId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
