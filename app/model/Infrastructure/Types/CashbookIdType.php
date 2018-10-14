<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\GuidType;
use Model\Cashbook\Cashbook\CashbookId;

final class CashbookIdType extends GuidType
{
    public function getName() : string
    {
        return 'cashbook_id';
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?CashbookId
    {
        if ($value === null) {
            return null;
        }

        /** @var string $value */
        return CashbookId::fromString($value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?string
    {
        if ($value === null) {
            return null;
        }

        /** @var CashbookId $value */
        return $value->toString();
    }
}
