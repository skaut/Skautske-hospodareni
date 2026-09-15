<?php

declare(strict_types=1);

namespace App\Model\Payment;

use Money\Money;
use Nette\SmartObject;

/**
 * @property int   $count
 * @property Money $amount
 */
class Summary
{
    use SmartObject;

    public function __construct(private int $count, private Money $amount)
    {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return new self($this->count + $other->count, $this->amount->add($other->amount));
    }
}
