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

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        assert($value instanceof FilePath);

        return $value->getPath();
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?FilePath
    {
        return $value === null ? null : FilePath::fromString($value);
    }
}
