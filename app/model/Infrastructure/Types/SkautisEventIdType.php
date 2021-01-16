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

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?SkautisEventId
    {
        if ($value === null) {
            return null;
        }

        return new SkautisEventId((int) $value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof SkautisEventId);

        return $value->toInt();
    }
}
