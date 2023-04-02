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

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Money|null
    {
        return $value === null ? null : Money::CZK(parent::convertToPHPValue($value, $platform));
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException('Only instances of ' . Money::class . 'allowed');
        }

        return $value->getAmount();
    }
}
