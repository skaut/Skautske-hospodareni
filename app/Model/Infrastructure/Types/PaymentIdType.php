<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Participant\PaymentId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use LogicException;

use function is_string;

final class PaymentIdType extends GuidType
{
    public function getName(): string
    {
        return 'payment_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?PaymentId
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException('Assertion failed.');
        }

        return PaymentId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof PaymentId) {
            throw new LogicException('Assertion failed.');
        }

        return $value->toString();
    }
}
