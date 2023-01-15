<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Event\SkautisEventId;

use function assert;

final class SkautisEventIdType extends GuidType
{
    public function getName(): string
    {
        return 'skautis_event_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): SkautisEventId|null
    {
        if ($value === null) {
            return null;
        }

        return new SkautisEventId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisEventId);

        return $value->toInt();
    }
}
