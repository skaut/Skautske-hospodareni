<?php

declare(strict_types=1);

namespace Model\Common;

use Cake\Chronos\ChronosDate;

use function basename;
use function count;
use function preg_match;
use function sprintf;
use function uniqid;

final class FilePath
{
    public static function generate(string $prefix, string $path): self
    {
        return new self(self::generatePath($prefix, $path));
    }

    public static function fromString(string $path): self
    {
        return new self($path);
    }

    private function __construct(private string $path)
    {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public static function generatePath(string $prefix, string $originalFileName): string
    {
        return sprintf('%s/%s/%s_%s', $prefix, ChronosDate::today()->format('Y/m'), uniqid(), $originalFileName);
    }

    public function equals(self $that): bool
    {
        return $this->getPath() === $that->getPath();
    }

    public function getOriginalFilename(): string
    {
        preg_match('/[0-9a-z]_(.*)/', basename($this->path), $matches);

        return count($matches) > 0 ? $matches[1] : basename($this->path);
    }
}
