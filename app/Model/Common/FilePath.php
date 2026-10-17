<?php

declare(strict_types=1);

namespace App\Model\Common;

use Cake\Chronos\ChronosDate;

use function basename;
use function count;
use function pathinfo;
use function preg_match;
use function sprintf;
use function strtolower;
use function uniqid;

use const PATHINFO_EXTENSION;

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

    /**
     * MIME typ podle přípony. Obsah souboru se proti povoleným typům ověřuje už při uložení skenu.
     */
    public function getContentType(): string
    {
        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));

        return IScanStorage::ALLOWED_MIME_TYPES[$extension] ?? 'application/octet-stream';
    }

    public function isPdf(): bool
    {
        return $this->getContentType() === IScanStorage::ALLOWED_MIME_TYPES['pdf'];
    }
}
