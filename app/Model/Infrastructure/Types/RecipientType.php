<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\Recipient;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use LogicException;

use function is_string;

class RecipientType extends StringType
{
    public function getName(): string
    {
        return 'recipient';
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof Recipient) {
            throw new LogicException('Assertion failed.');
        }

        return $value->getName();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Recipient
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException('Assertion failed.');
        }

        return new Recipient($value);
    }
}
