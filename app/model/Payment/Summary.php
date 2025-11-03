<?php

declare(strict_types=1);

namespace Model\Payment;

use Nette\SmartObject;

/**
 * @property int   $count
 * @property float $amount
 */
class Summary
{
    use SmartObject;

    public function __construct(private int $count, private float $amount)
    {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function add(self $other): self
    {
        return new self($this->count + $other->count, $this->amount + $other->amount);
    }
}
