<?php

declare(strict_types=1);

namespace Model\Cashbook\Events;

use Model\Cashbook\Cashbook\CashbookId;

final class ChitWasAdded
{
    /** @var CashbookId */
    private $cashbookId;

    public function __construct(CashbookId $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }
}
