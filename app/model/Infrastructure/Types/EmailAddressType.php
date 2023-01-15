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

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof EmailAddress);

        return $value->getValue();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): EmailAddress|null
    {
        return $value === null ? null : new EmailAddress($value);
    }
}
