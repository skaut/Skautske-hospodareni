<?php

namespace Model\Utils;

use Money\Money;
use Nette\StaticClass;

final class MoneyFactory
{
    use StaticClass;

    public static function fromFloat(float $amount): Money
    {
        return Money::CZK(intval($amount * 100));
    }

    public static function toFloat(Money $money): float
    {
        return intval($money->getAmount()) / 100;
    }

}
