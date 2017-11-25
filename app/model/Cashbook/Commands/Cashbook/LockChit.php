<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Handlers\Cashbook\LockChitHandler;

/**
 * @see LockChitHandler
 */
final class LockChit
{

    /** @var int */
    private $cashbookId;

    /** @var int */
    private $chitId;

    /** @var int */
    private $userId;

    public function __construct(int $cashbookId, int $chitId, int $userId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId = $chitId;
        $this->userId = $userId;
    }

    public function getCashbookId(): int
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
