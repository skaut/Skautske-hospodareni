<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Common\EmailAddress;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use LogicException;

class EmailAddressType extends StringType
{
    public function getName(): string
    {
        return 'email_address';
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof EmailAddress) {
            throw new LogicException('Assertion failed.');
        }

        return $value->getValue();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?EmailAddress
    {
        return $value === null ? null : new EmailAddress($value);
    }
}
