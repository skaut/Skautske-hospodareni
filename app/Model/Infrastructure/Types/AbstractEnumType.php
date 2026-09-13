<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use Consistence\Enum\Enum;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

/**
 * Mapuje Consistence enum na string sloupec.
 *
 * Nahrazuje consistence-doctrine (@Enum anotace + postLoad listener) – hydrataci řeší přímo
 * convertToPHPValue, takže odpadá závislost na anotacích.
 */
abstract class AbstractEnumType extends StringType
{
    /** @return class-string<Enum> */
    abstract protected function enumClass(): string;

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Enum
    {
        if ($value === null) {
            return null;
        }

        $class = $this->enumClass();

        return $class::get($value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof Enum ? (string) $value->getValue() : (string) $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}
