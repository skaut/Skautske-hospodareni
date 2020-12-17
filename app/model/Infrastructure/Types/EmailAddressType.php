<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Common\EmailAddress;

class EmailAddressType extends StringType
{
    public function getName() : string
    {
        return 'email_address';
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?string
    {
        /** @var $value EmailAddress */
        return $value === null ? null : $value->getValue();
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?EmailAddress
    {
        return $value === null ? null : new EmailAddress($value);
    }
}
