<?php

declare(strict_types=1);

namespace Warhuhn\Doctrine\DBAL\Types;

use Cake\Chronos\Chronos;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeType;

class ChronosDateTimeType extends DateTimeType
{
    public const CHRONOS_DATETIME = 'chronos_datetime';

    public function getName(): string
    {
        return self::CHRONOS_DATETIME;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): Chronos|null
    {
        if ($value === null) {
            return null;
        }

        $dateTime = parent::convertToPHPValue($value, $platform);

        return Chronos::instance($dateTime);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
