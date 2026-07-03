<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Events;

use App\Model\Cashbook\Cashbook\CashbookId;

final class ChitWasAdded
{
    public function __construct(private CashbookId $cashbookId)
    {
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
