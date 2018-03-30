<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\LockChitHandler;

/**
 * @see LockChitHandler
 */
final class LockChit
{

    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var int */
    private $userId;

    public function __construct(CashbookId $cashbookId, int $chitId, int $userId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId = $chitId;
        $this->userId = $userId;
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
