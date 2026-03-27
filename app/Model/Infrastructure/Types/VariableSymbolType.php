<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Payment\VariableSymbol;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

class VariableSymbolType extends StringType
{
    public const NAME = 'variable_symbol';
    public const LENGTH = 10;

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDefaultLength(AbstractPlatform $platform): int
    {
        return self::LENGTH;
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?VariableSymbol
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        return new VariableSymbol($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
