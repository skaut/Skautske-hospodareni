<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Event\SkautisCampId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use LogicException;

final class SkautisCampIdType extends GuidType
{
    public function getName(): string
    {
        return 'skautis_camp_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?SkautisCampId
    {
        if ($value === null) {
            return null;
        }

        return new SkautisCampId((int) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof SkautisCampId) {
            throw new LogicException('Assertion failed.');
        }

        return $value->toInt();
    }
}
