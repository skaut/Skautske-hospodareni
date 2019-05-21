<?php

declare(strict_types=1);

namespace Model\Cashbook;

use InvalidArgumentException;
use Model\Utils\MoneyFactory;
use Money\Money;
use function sprintf;

final class InvalidAmount extends InvalidArgumentException
{
    public static function notRoundedCashAmount(Money $amount) : self
    {
        return new self(sprintf(
            'Chits paid by cash cannot must be rounded to whole CZK, %.2f CZK given',
            MoneyFactory::toFloat($amount)
        ));
    }
}
