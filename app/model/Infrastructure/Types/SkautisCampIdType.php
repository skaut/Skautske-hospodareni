<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Event\SkautisCampId;

use function assert;

final class SkautisCampIdType extends GuidType
{
    public function getName(): string
    {
        return 'skautis_camp_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): SkautisCampId|null
    {
        if ($value === null) {
            return null;
        }

        return new SkautisCampId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisCampId);

        return $value->toInt();
    }
}
