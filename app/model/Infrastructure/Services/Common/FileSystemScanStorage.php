<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Common;

use finfo;
use InvalidArgumentException;
use Model\Common\Exception\InvalidScanFile;
use Model\Common\File;
use Model\Common\FileNotFound;
use Model\Common\IScanStorage;
use Nette\Utils\FileSystem;
use const FILEINFO_MIME_TYPE;
use function in_array;
use function is_dir;
use function is_file;
use function sprintf;

final class FileSystemScanStorage implements IScanStorage
{
    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        if (! FileSystem::isAbsolute($directory)) {
            throw new InvalidArgumentException('Path to directory must be absolute');
        }

        if (! is_dir($directory)) {
            throw new InvalidArgumentException(sprintf('"%s" is not valid directory', $directory));
        }

        $this->directory = $directory;
    }

    public function get(string $path) : File
    {
        $absolutePath = $this->getAbsolutePath($path);

        if (! is_file($absolutePath)) {
            throw FileNotFound::withPath($absolutePath);
        }

        return new File($absolutePath, $path);
    }

    public function save(string $path, string $contents) : void
    {
        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, IScanStorage::ALLOWED_MIME_TYPES, true)) {
            throw InvalidScanFile::invalidType($mimeType);
        }

        FileSystem::write($this->getAbsolutePath($path), $contents);
    }

    public function delete(string $path) : void
    {
        FileSystem::delete($this->getAbsolutePath($path));
    }

    private function detectMimeType(string $contents) : ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: null;
    }

    private function getAbsolutePath(string $path) : string
    {
        return $this->directory . '/' . $path;
    }
}
