<?php

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Cashbook\Cashbook\ChitNumber;

class ChitNumberType extends StringType
{

    public function getName(): string
    {
        return 'chit_number';
    }

    public function getDefaultLength(AbstractPlatform $platform): int
    {
        return 5;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        /* @var $value ChitNumber */
        return $value === NULL ? NULL : $value->toString();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?ChitNumber
    {
        return $value === NULL ? NULL : new ChitNumber($value);
    }

}
