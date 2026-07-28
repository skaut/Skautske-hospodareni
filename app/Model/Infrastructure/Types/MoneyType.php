<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use Money\Money;

/**
 * INT sloupec mapovaný na Money.
 *
 * Nedědí z IntegerType – ten má v DBAL 4 pevný návratový typ `?int` u convertToPHPValue.
 */
class MoneyType extends Type
{
    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Money
    {
        return $value === null ? null : Money::CZK((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string
    {
        if (! $value instanceof Money) {
            throw new InvalidArgumentException('Only instances of '.Money::class.'allowed');
        }

        return $value->getAmount();
    }
}
