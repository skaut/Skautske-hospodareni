<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\LockCashbookHandler;

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
