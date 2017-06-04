<?php

namespace Model\DTO\Payment;

use Nette\SmartObject;

/**
 * @property-read int $count
 * @property-read float $amount
 * @property-read float $percentage
 */
class Summary
{

    use SmartObject;

    /** @var int */
    private $count;

    /** @var float */
    private $amount;

    /** @var float */
    private $percentage;

    public function __construct(int $count, float $amount, float $percentage)
    {
        $this->count = $count;
        $this->amount = $amount;
        $this->percentage = $percentage;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getPercentage(): float
    {
        return $this->percentage;
    }

}
