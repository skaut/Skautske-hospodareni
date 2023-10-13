<?php

declare(strict_types=1);

namespace Model\Grant;

use Money\Money;
use Nette\SmartObject;

/**
 * @property-read SkautisGrantId $id
 * @property-read Money $amountMax
 * @property-read Money $amountMaxReal
 */
class Grant
{
    use SmartObject;

    public function __construct(
        private SkautisGrantId $id,
        private Money $amountMax,
        private Money $amountMaxReal,
    ) {
    }

    public function getId(): SkautisGrantId
    {
        return $this->id;
    }

    public function getAmountMax(): Money
    {
        return $this->amountMax;
    }

    public function getAmountMaxReal(): Money
    {
        return $this->amountMaxReal;
    }
}
