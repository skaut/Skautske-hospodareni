<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Handlers\Cashbook\RemoveChitFromCashbookHandler;

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
