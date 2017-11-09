<?php

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Payment\VariableSymbol;

class VariableSymbolType extends StringType
{

    public const NAME = "variable_symbol";

    public function getName(): string
    {
        return self::NAME;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?VariableSymbol
    {
        if ($value === NULL || $value === '') {
            return NULL;
        }

        return new VariableSymbol($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if($value === NULL) {
            return NULL;
        }

        return (string) $value;
    }

}
