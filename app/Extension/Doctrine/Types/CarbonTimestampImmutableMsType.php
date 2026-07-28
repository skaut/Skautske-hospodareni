<?php

declare(strict_types=1);

namespace Extension\Doctrine\Types;

use Carbon\CarbonImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function is_numeric;

/**
 * BIGINT sloupec (unixový čas v ms) mapovaný na CarbonImmutable.
 *
 * Nedědí z BigIntType – ten má v DBAL 4 pevný návratový typ `int|string|null` u convertToPHPValue.
 */
class CarbonTimestampImmutableMsType extends Type
{
    public const NAME = 'carbon_timestamp_immutable_ms';

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?int
    {
        if ($value instanceof CarbonImmutable) {
            return $value->getTimestampMs();
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Carbon timestamp field accepts only CarbonImmutable|null');
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CarbonImmutable
    {
        if (is_numeric($value)) {
            return CarbonImmutable::createFromTimestampMs((int) $value);
        }

        if ($value === null) {
            return null;
        }

        throw new InvalidArgumentException('Carbon timestamp field has to be saved as int|null in database');
    }
}
