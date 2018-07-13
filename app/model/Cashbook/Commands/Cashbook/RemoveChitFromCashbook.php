<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\RemoveChitFromCashbookHandler;

/**
 * @see RemoveChitFromCashbookHandler
 */
final class RemoveChitFromCashbook
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
