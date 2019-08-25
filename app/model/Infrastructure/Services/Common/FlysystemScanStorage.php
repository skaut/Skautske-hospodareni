<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Common;

use Assert\Assertion;
use finfo;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Model\Common\Exception\InvalidScanFile;
use Model\Common\File;
use Model\Common\FileNotFound;
use Model\Common\FilePath;
use Model\Common\IScanStorage;
use const FILEINFO_MIME_TYPE;
use function GuzzleHttp\Psr7\stream_for;
use function in_array;

final class FlysystemScanStorage implements IScanStorage
{
    /** @var FilesystemInterface */
    private $filesystem;

    public function __construct(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function get(FilePath $path) : File
    {
        try {
            $contents = $this->filesystem->readStream($path->getPath());

            Assertion::isResource($contents);

            return new File(stream_for($contents), $path);
        } catch (FileNotFoundException $e) {
            throw new FileNotFound($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function save(FilePath $path, string $contents) : void
    {
        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, IScanStorage::ALLOWED_MIME_TYPES, true)) {
            throw InvalidScanFile::invalidType($mimeType);
        }

        $this->filesystem->write($path->getPath(), $contents);
    }

    public function delete(FilePath $path) : void
    {
        try {
            $this->filesystem->delete($path->getPath());
        } catch (FileNotFoundException $e) {
            // File was probably deleted before
        }
    }

    private function detectMimeType(string $contents) : ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: null;
    }
}
