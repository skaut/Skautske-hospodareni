<?php

declare(strict_types=1);

namespace Extension\Doctrine\Types;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BigIntType;
use InvalidArgumentException;

use function is_numeric;

class CarbonTimestampImmutableMsType extends BigIntType
{
    public const NAME = 'carbon_timestamp_immutable_ms';

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): int|null
    {
        if ($value instanceof CarbonImmutable) {
            return $value->getTimestampMs();
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Carbon timestamp field accepts only CarbonImmutable|null');
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): CarbonImmutable|null
    {
        if (is_numeric($value)) {
            return CarbonImmutable::createFromTimestampMs((int) $value);
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Carbon timestamp field has to be saved as int|null in database');
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
