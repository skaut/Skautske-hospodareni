<?php

declare(strict_types=1);

namespace App\Model\DTO\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use Nette\SmartObject;

/**
 * @property int        $id
 * @property CashbookId $cashbookId
 * @property int        $year
 */
class UnitCashbook
{
    use SmartObject;

    public function __construct(private int $id, private CashbookId $cashbookId, private int $year)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
