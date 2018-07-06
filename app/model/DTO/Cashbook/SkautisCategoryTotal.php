<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Money\Money;

class SkautisCategoryTotal
{
    /** @var Money */
    private $amount;

    /** @var bool */
    private $consistent;

    public function __construct(Money $amount, bool $consistent)
    {
        $this->amount     = $amount;
        $this->consistent = $consistent;
    }

    public function getAmount() : Money
    {
        return $this->amount;
    }

    public function isConsistent() : bool
    {
        return $this->consistent;
    }
}
