<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\ChitNumber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

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

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof ChitNumber);

        return $value->toString();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?ChitNumber
    {
        return $value === null ? null : new ChitNumber($value);
    }
}
