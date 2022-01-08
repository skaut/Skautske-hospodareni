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

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SkautisEducationId
    {
        if ($value === null) {
            return null;
        }

        return new SkautisEducationId((int) $value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisEducationId);

        return $value->toInt();
    }
}
