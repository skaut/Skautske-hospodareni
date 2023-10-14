<?php

declare(strict_types=1);

namespace Model\Grant;

use Money\Money;
use Nette\SmartObject;

/**
 * @property-read SkautisGrantId $id
 * @property-read string $state
 * @property-read Money $amountMax
 * @property-read Money $amountPerPersonDays
 * @property-read float $costRatio
 */
class Grant
{
    use SmartObject;

    public function __construct(
        private SkautisGrantId $id,
        private string $state,
        private Money $amountMax,
        private Money $amountPerPersonDays,
        private float $costRatio,
    ) {
    }

    public function getId(): SkautisGrantId
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getAmountMax(): Money
    {
        return $this->amountMax;
    }

    public function getAmountPerPersonDays(): Money
    {
        return $this->amountPerPersonDays;
    }

    public function getCostRatio(): float
    {
        return $this->costRatio;
    }
}
