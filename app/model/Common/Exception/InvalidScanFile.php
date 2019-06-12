<?php

declare(strict_types=1);

namespace Model\Common\Exception;

use Exception;
use function sprintf;

final class InvalidScanFile extends Exception
{
    public static function invalidType(string $fileMimeType) : self
    {
        return new self(sprintf('Invalid scan type "%s"', $fileMimeType));
    }
}
