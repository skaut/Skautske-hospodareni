<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\LockChitHandler;

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
