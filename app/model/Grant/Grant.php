<?php

declare(strict_types=1);

namespace Model\Grant;

use Money\Money;
use Nette\SmartObject;

/**
 * @property-read SkautisGrantId $id
 * @property-read string $state
 * @property-read Money $amountMax
 * @property-read Money $amountMaxReal
 * @property-read float $costRatio
 * @property-read Money $remainingPay
 */
class Grant
{
    use SmartObject;

    public function __construct(
        private SkautisGrantId $id,
        private string $state,
        private Money $amountMax,
        private Money $amountMaxReal,
        private float $costRatio,
        private Money $remainingPay,
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

    public function getAmountMaxReal(): Money
    {
        return $this->amountMaxReal;
    }

    public function getCostRatio(): float
    {
        return $this->costRatio;
    }

    public function getRemainingPay(): Money
    {
        return $this->remainingPay;
    }
}
