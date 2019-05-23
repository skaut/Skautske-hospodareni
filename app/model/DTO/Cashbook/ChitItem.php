<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Operation;
use Nette\SmartObject;

/**
 * @property-read Amount   $amount
 * @property-read Category $category
 * @property-read string   $purpose
 */
class ChitItem
{
    use SmartObject;

    /** @var Amount */
    private $amount;

    /** @var Category */
    private $category;

    /** @var string */
    private $purpose;

    public function __construct(Amount $amount, Category $category, string $purpose)
    {
        $this->amount   = $amount;
        $this->category = $category;
        $this->purpose  = $purpose;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategory() : Category
    {
        return $this->category;
    }

    public function getPurpose() : string
    {
        return $this->purpose;
    }

    public function getSignedAmount() : float
    {
        $amount = $this->amount->toFloat();

        if ($this->category->getOperationType()->equalsValue(Operation::EXPENSE)) {
            return -1 * $amount;
        }

        return $amount;
    }
}
