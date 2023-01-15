<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Common\FilePath;

use function assert;

class FilePathType extends StringType
{
    public function getName(): string
    {
        return 'file_path';
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof FilePath);

        return $value->getPath();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): FilePath|null
    {
        return $value === null ? null : FilePath::fromString($value);
    }
}
