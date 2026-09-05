<?php

declare(strict_types=1);

namespace App\Model\DTO\Cashbook;

use App\Model\Cashbook\Cashbook\Amount;
use App\Model\Cashbook\Operation;
use Money\Money;
use Nette\SmartObject;

/**
 * @property Amount   $amount
 * @property Category $category
 * @property string   $purpose
 */
class ChitItem
{
    use SmartObject;

    public function __construct(private Amount $amount, private Category $category, private string $purpose)
    {
    }

    public function getAmount(): Amount
    {
        return $this->amount;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function getPurpose(): string
    {
        return $this->purpose;
    }

    public function getSignedAmount(): Money
    {
        $amount = $this->amount->toMoney();

        if ($this->category->getOperationType()->equalsValue(Operation::EXPENSE)) {
            return $amount->multiply(-1);
        }

        return $amount;
    }
}
