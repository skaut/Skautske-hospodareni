<?php

declare(strict_types=1);

namespace App\Model\Utils;

use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Nette\StaticClass;

use function abs;
use function floor;
use function intdiv;
use function intval;
use function preg_match;
use function round;
use function str_pad;
use function str_replace;
use function trim;

final class MoneyFactory
{
    use StaticClass;

    public static function fromDecimal(string $amount): Money
    {
        $amount = str_replace(',', '.', trim($amount));
        if (preg_match('/^(?<sign>-?)(?<whole>\d+)(?:\.(?<fraction>\d{1,2}))?$/', $amount, $matches) !== 1) {
            throw new InvalidArgumentException('Amount must be a decimal number with at most two decimal places.');
        }

        $minorUnits = ((int) $matches['whole'] * 100) + (int) str_pad($matches['fraction'] ?? '', 2, '0');

        return Money::CZK(($matches['sign'] === '-' ? -1 : 1) * $minorUnits);
    }

    public static function toDecimal(Money $money): string
    {
        $amount = (int) $money->getAmount();
        $sign = $amount < 0 ? '-' : '';
        $amount = abs($amount);

        return $sign.intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function fromFloat(float $amount): Money
    {
        return new Money((int) round($amount * 100), new Currency('CZK'));
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
     * Removes cents from amount.
     */
    public static function floor(Money $money): Money
    {
        $floatAmount = self::toFloat($money);

        return self::fromFloat(floor($floatAmount));
    }
}
