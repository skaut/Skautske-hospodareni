<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Brick\Math\BigDecimal;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DecimalType;
use InvalidArgumentException;

use function is_numeric;

class BigDecimalType extends DecimalType
{
    public const NAME = 'big_decimal';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value instanceof BigDecimal) {
            return (string) $value;
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('BigDecimal field accepts only BigDecimal|null');
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): BigDecimal|null
    {
        if (is_numeric($value)) {
            return BigDecimal::of($value);
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('BigDecimal field has to be saved as string|null in database');
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
