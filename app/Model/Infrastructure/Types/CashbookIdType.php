<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Cashbook\Cashbook\CashbookId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use LogicException;

use function is_string;

final class CashbookIdType extends GuidType
{
    public function getName(): string
    {
        return 'cashbook_id';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?CashbookId
    {
        if ($value === null) {
            return null;
        }

        if (! is_string($value)) {
            throw new LogicException('Assertion failed.');
        }

        return CashbookId::fromString($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (! $value instanceof CashbookId) {
            throw new LogicException('Assertion failed.');
        }

        return $value->toString();
    }
}
