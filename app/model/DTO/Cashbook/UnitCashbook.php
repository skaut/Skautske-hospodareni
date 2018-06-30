<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;

class UnitCashbook
{

    /** @var CashbookId */
    private $cashbookId;

    public function __construct(CashbookId $cashbookId)
    {
        $this->cashbookId = $cashbookId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

}
