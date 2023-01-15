<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Cashbook\Cashbook\ChitNumber;

use function assert;

class ChitNumberType extends StringType
{
    public function getName(): string
    {
        return 'chit_number';
    }

    public function getDefaultLength(AbstractPlatform $platform): int
    {
        return 5;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof ChitNumber);

        return $value->toString();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ChitNumber|null
    {
        return $value === null ? null : new ChitNumber($value);
    }
}
