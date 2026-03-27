<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use App\Model\Common\FilePath;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

use function assert;

class FilePathType extends StringType
{
    public function getName(): string
    {
        return 'file_path';
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof FilePath);

        return $value->getPath();
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?FilePath
    {
        return $value === null ? null : FilePath::fromString($value);
    }
}
