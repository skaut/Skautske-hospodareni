<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;

class UnitCashbook
{
    /** @var CashbookId */
    private $cashbookId;

    /** @var int */
    private $year;

    public function __construct(CashbookId $cashbookId, int $year)
    {
        $this->cashbookId = $cashbookId;
        $this->year       = $year;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
