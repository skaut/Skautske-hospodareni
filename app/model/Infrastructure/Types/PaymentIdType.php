<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Participant\PaymentId;

use function assert;
use function is_string;

final class PaymentIdType extends GuidType
{
    public function getName(): string
    {
        return 'payment_id';
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?PaymentId
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return PaymentId::fromString($value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof PaymentId);

        return $value->toString();
    }
}
