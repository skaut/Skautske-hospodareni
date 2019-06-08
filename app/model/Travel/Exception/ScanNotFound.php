<?php

declare(strict_types=1);

namespace Model\Travel;

use Exception;
use function sprintf;

final class ScanNotFound extends Exception
{
    public static function withPath(string $path) : self
    {
        return new self(sprintf('Scan "%s" does not exist in vehicle', $path));
    }
}
