<?php

declare(strict_types=1);

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

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
