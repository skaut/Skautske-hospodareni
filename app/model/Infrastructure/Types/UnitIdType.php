<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Common\UnitId;

use function assert;

final class UnitIdType extends GuidType
{
    public function getName(): string
    {
        return 'unit_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): UnitId|null
    {
        if ($value === null) {
            return null;
        }

        return new UnitId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof UnitId);

        return $value->toInt();
    }
}
