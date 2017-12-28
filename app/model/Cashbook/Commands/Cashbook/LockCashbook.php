<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\LockCashbookHandler;

/**
 * @see LockCashbookHandler
 */
final class LockCashbook
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $userId;

    public function __construct(int $cashbookId, int $userId)
    {
        $this->cashbookId = $cashbookId;
        $this->userId = $userId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

}
