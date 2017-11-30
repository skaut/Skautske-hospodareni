<?php

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Cashbook\Cashbook\Recipient;

class RecipientType extends StringType
{

    public function getName(): string
    {
        return 'recipient';
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        /* @var $value Recipient */
        return $value === NULL ? NULL : $value->getName();
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Recipient
    {
        return $value === NULL ? NULL : new Recipient($value);
    }

}
