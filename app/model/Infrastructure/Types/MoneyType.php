<?php

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;
use Money\Currency;
use Money\Money;

class MoneyType extends DecimalType
{

    public const NAME = "money";
    private const SUBUNITS = "100";
    private const CURRENCY = "CZK";

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): Money
    {
        $stringValue = parent::convertToPHPValue($value, $platform);
        return new Money(bcmul($stringValue, self::SUBUNITS), new Currency(self::CURRENCY));
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if(! $value instanceof Money) {
            throw new \InvalidArgumentException("Only instances of " . Money::class . "allowed");
        }
        return bcdiv($value->getAmount(), self::SUBUNITS, 2);
    }

}
