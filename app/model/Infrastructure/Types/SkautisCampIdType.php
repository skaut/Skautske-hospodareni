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

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SkautisCampId
    {
        if ($value === null) {
            return null;
        }

        return new SkautisCampId((int) $value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisCampId);

        return $value->toInt();
    }
}
