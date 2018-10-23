<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Common\UnitId;

final class UnitIdType extends GuidType
{
    public function getName() : string
    {
        return 'unit_id';
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?UnitId
    {
        if ($value === null) {
            return null;
        }

        return new UnitId((int) $value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?int
    {
        if ($value === null) {
            return null;
        }

        /** @var UnitId $value */
        return $value->toInt();
    }
}
