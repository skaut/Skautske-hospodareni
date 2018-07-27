<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Payment\VariableSymbol;

class VariableSymbolType extends StringType
{
    public const NAME   = 'variable_symbol';
    public const LENGTH = 10;

    public function getName() : string
    {
        return self::NAME;
    }

    public function getDefaultLength(AbstractPlatform $platform) : int
    {
        return self::LENGTH;
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?VariableSymbol
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        return new VariableSymbol($value);
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
