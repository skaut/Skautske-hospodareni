<?php

declare(strict_types=1);

namespace Warhuhn\Doctrine\DBAL\Types;

use Cake\Chronos\ChronosDate;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateType;

class ChronosDateType extends DateType
{
    public const CHRONOS_DATE = 'chronos_date';

    public function getName(): string
    {
        return self::CHRONOS_DATE;
    }

    /**
     * {@inheritDoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ChronosDate|null
    {
        if ($value === null) {
            return null;
        }

        $dateTime = parent::convertToPHPValue($value, $platform);

        return new ChronosDate($dateTime);
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
