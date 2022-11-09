<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Cashbook\Cashbook\Recipient;

use function assert;
use function is_string;

class RecipientType extends StringType
{
    public function getName(): string
    {
        return 'recipient';
    }

    /** @param mixed $value */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof Recipient);

        return $value->getName();
    }

    /** @param mixed $value */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Recipient
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return new Recipient($value);
    }
}
