<?php

declare(strict_types=1);

namespace Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;
use Model\Common\FilePath;

class FilePathType extends StringType
{
    public function getName() : string
    {
        return 'file_path';
    }

    /**
     * @param mixed $value
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform) : ?string
    {
        /** @var FilePath $value */
        return $value === null ? null : $value->getPath();
    }

    /**
     * @param mixed $value
     */
    public function convertToPHPValue($value, AbstractPlatform $platform) : ?FilePath
    {
        return $value === null ? null : FilePath::fromString($value);
    }
}
