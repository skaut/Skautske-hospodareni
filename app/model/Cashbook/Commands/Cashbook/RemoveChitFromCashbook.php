<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Handlers\Cashbook\RemoveChitFromCashbookHandler;

/** @see RemoveChitFromCashbookHandler */
final class RemoveChitFromCashbook
{
    public function __construct(private CashbookId $cashbookId, private int $chitId)
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
}
