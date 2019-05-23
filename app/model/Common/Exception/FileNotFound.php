<?php

declare(strict_types=1);

namespace Model\Common;

use Exception;
use function sprintf;

final class FileNotFound extends Exception
{
    public static function withPath(string $path) : self
    {
        return new self(sprintf('File "%s" was not found', $path));
    }
}
