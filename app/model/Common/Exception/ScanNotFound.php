<?php

declare(strict_types=1);

namespace Model\Common;

use Exception;
use function sprintf;

final class ScanNotFound extends Exception
{
    public static function withPath(FilePath $path) : self
    {
        return new self(sprintf('Scan "%s" does not exists', $path->getPath()));
    }
}
