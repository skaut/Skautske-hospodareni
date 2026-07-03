<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\IntegerType;

final class Int64Type extends IntegerType
{
    public const NAME = 'int64';

    public function getName(): string
    {
        return self::NAME;
    }

    /** @param array<string, mixed> $column */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'BIGINT';
    }
}
