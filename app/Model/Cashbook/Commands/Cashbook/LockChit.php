<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\LockChitHandler;

/** @see LockChitHandler */
final class LockChit
{
    public function __construct(private CashbookId $cashbookId, private int $chitId, private int $userId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
