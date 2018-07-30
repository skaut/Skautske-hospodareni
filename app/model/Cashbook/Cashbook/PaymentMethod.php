<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Consistence\Enum\Enum;

final class PaymentMethod extends Enum
{
    public const CASH = 'cash';
    public const BANK = 'bank';

    public static function CASH() : self
    {
        return self::get(self::CASH);
    }

    public static function BANK() : self
    {
        return self::get(self::BANK);
    }

    public function toString() : string
    {
        return (string) $this->getValue();
    }

    public function __toString() : string
    {
        return $this->toString();
    }
}
