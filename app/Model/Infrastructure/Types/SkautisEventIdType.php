<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Event\SkautisEventId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use LogicException;

final class SkautisEventIdType extends GuidType
{
    public function getName(): string
    {
        return 'skautis_event_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SkautisEventId
    {
        if ($value === null) {
            return null;
        }

        return new SkautisEventId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof SkautisEventId) {
            throw new LogicException('Assertion failed.');
        }

        return $value->toInt();
    }
}
