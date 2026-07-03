<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Common\UnitId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;

use function assert;

final class UnitIdType extends GuidType
{
    public function getName(): string
    {
        return 'unit_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?UnitId
    {
        if ($value === null) {
            return null;
        }

        return new UnitId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof UnitId);

        return $value->toInt();
    }
}
