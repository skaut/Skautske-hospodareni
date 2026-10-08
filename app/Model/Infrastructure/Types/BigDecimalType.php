<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function is_numeric;

/**
 * DECIMAL sloupec mapovaný na Brick\Math\BigDecimal.
 *
 * Nedědí z DecimalType – ten má v DBAL 4 pevný návratový typ `?string` u convertToPHPValue.
 */
class BigDecimalType extends Type
{
    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDecimalTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value instanceof BigDecimal) {
            return (string) $value;
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('BigDecimal field accepts only BigDecimal|null');
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?BigDecimal
    {
        if (is_numeric($value)) {
            return BigDecimal::of((string) $value);
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('BigDecimal field has to be saved as string|null in database');
    }
}
