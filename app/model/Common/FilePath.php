<?php

declare(strict_types=1);

namespace Model\Common;

use Cake\Chronos\Date;
use function sprintf;
use function uniqid;

final class FilePath
{
    /** @var string */
    private $path;

    public static function generate(string $prefix, string $path) : self
    {
        return new self(self::generatePath($prefix, $path));
    }

    public static function fromString(string $path) : self
    {
        return new self($path);
    }

    private function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public static function generatePath(string $prefix, string $originalFileName) : string
    {
        return sprintf('%s/%s/%s_%s', $prefix, Date::today()->format('Y/m'), uniqid(), $originalFileName);
    }

    public function equals(self $that) : bool
    {
        return $this->getPath() === $that->getPath();
    }
}
