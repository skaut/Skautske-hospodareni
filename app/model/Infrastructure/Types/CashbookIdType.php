<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Cashbook\Cashbook\CashbookId;

use function assert;
use function is_string;

final class CashbookIdType extends GuidType
{
    public function getName(): string
    {
        return 'cashbook_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): CashbookId|null
    {
        if ($value === null) {
            return null;
        }

        assert(is_string($value));

        return CashbookId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof CashbookId);

        return $value->toString();
    }
}
