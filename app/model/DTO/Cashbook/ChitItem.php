<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Model\Cashbook\Cashbook\Amount;
use Model\Cashbook\Operation;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read Amount $amount
 * @property-read Category $category
 */
class ChitItem
{
    use SmartObject;

    /** @var int */
    private $id;

    /** @var Amount */
    private $amount;

    /** @var Category */
    private $category;

    public function __construct(int $id, Amount $amount, Category $category)
    {
        $this->id       = $id;
        $this->amount   = $amount;
        $this->category = $category;
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getAmount() : Amount
    {
        return $this->amount;
    }

    public function getCategory() : Category
    {
        return $this->category;
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
