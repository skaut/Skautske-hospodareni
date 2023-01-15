<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Event\SkautisEducationId;

use function assert;

final class SkautisEducationIdType extends GuidType
{
    public function getName(): string
    {
        return 'skautis_education_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): SkautisEducationId|null
    {
        if ($value === null) {
            return null;
        }

        return new SkautisEducationId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisEducationId);

        return $value->toInt();
    }
}
