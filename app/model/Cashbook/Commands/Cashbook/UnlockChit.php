<?php

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\UnlockChitHandler;

/**
 * @see UnlockChitHandler
 */
final class UnlockChit
{

    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $chitId;

    public function __construct(CashbookId $cashbookId, int $chitId)
    {
        $this->cashbookId = $cashbookId;
        $this->chitId = $chitId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

}
