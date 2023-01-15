<?php

declare(strict_types=1);

namespace Model\DTO\Cashbook;

use Money\Money;

class SkautisCategoryTotal
{
    public function __construct(private Money $amount, private bool $consistent)
    {
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function isConsistent(): bool
    {
        return $this->consistent;
    }
}
