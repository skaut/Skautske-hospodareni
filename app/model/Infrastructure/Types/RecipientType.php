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

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof Recipient);

        return $value->getName();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): Recipient|null
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return new Recipient($value);
    }
}
