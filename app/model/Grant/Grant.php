<?php

declare(strict_types=1);

namespace Model\Grant;

use Money\Money;
use Nette\SmartObject;

/**
 * @property-read SkautisGrantId $id
 * @property-read Money $amountMax
 */
class Grant
{
    use SmartObject;

    public function __construct(
        private SkautisGrantId $id,
        private Money $amountMax,
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
}
