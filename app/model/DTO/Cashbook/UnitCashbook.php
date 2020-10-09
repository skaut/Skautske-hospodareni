<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\CashbookId;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read CashbookId $cashbookId
 * @property-read int $year
 */
class UnitCashbook
{
    use SmartObject;

    private int $id;

    private CashbookId $cashbookId;

    private int $year;

    public function __construct(int $id, CashbookId $cashbookId, int $year)
    {
        $this->id         = $id;
        $this->cashbookId = $cashbookId;
        $this->year       = $year;
    }

    public function getId() : int
    {
        return $this->id;
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
