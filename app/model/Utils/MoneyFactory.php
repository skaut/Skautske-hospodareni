<?php

namespace Model\Utils;

use Money\Currency;
use Money\Money;
use Nette\StaticClass;

final class MoneyFactory
{
    use StaticClass;

    public static function fromFloat(float $amount): Money
    {
        return new Money((int) round($amount * 100), new Currency("CZK"));
    }

    public static function toFloat(Money $money): float
    {
        return intval($money->getAmount()) / 100;
    }

    public static function zero(): Money
    {
        return self::fromFloat(0);
    }

    /**
     * Removes cents from amount
     */
    public static function floor(Money $money): Money
    {
        $floatAmount = self::toFloat($money);

        return self::fromFloat(floor($floatAmount));
    }

}
