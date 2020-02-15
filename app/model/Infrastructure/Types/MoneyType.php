<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;
use InvalidArgumentException;
use Money\Money;

class MoneyType extends IntegerType
{
    public const NAME = 'money';

    public function getName() : string
    {
        return self::NAME;
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : Money
    {
        return Money::CZK(parent::convertToPHPValue($value, $platform));
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : string
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException('Only instances of ' . Money::class . 'allowed');
        }

        return $value->getAmount();
    }
}
