<?php

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;
use Money\Money;

class MoneyType extends DecimalType
{

    public const NAME = "money";
    private const SUBUNITS = "100";

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): Money
    {
        $stringValue = parent::convertToPHPValue($value, $platform);
        return Money::CZK(bcmul($stringValue, self::SUBUNITS));
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): string
    {
        if(! $value instanceof Money) {
            throw new \InvalidArgumentException("Only instances of " . Money::class . "allowed");
        }
        return bcdiv($value->getAmount(), self::SUBUNITS, 2);
    }

}
