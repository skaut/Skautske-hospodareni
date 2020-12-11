<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Common\EmailAddress;

use function assert;

class EmailAddressType extends StringType
{
    public function getName(): string
    {
        return 'email_address';
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof EmailAddress);

        return $value->getValue();
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?EmailAddress
    {
        return $value === null ? null : new EmailAddress($value);
    }
}
