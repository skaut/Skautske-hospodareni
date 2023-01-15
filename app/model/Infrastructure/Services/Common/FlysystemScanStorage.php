<?php

declare(strict_types=1);

namespace Model\Infrastructure\Services\Common;

use Assert\Assertion;
use finfo;
use GuzzleHttp\Psr7\Utils;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToDeleteFile;
use League\Flysystem\UnableToReadFile;
use Model\Common\Exception\InvalidScanFile;
use Model\Common\File;
use Model\Common\FileNotFound;
use Model\Common\FilePath;
use Model\Common\IScanStorage;

use function in_array;

use const FILEINFO_MIME_TYPE;

final class FlysystemScanStorage implements IScanStorage
{
    public function __construct(private FilesystemOperator $filesystem)
    {
    }

    public function get(FilePath $path): File
    {
        try {
            $contents = $this->filesystem->readStream($path->getPath());

            Assertion::isResource($contents);

            return new File(Utils::streamFor($contents), $path);
        } catch (UnableToReadFile $e) {
            throw new FileNotFound($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function save(FilePath $path, string $contents): void
    {
        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, IScanStorage::ALLOWED_MIME_TYPES, true)) {
            throw InvalidScanFile::invalidType($mimeType);
        }

        $this->filesystem->write($path->getPath(), $contents);
    }

    public function delete(FilePath $path): void
    {
        try {
            $this->filesystem->delete($path->getPath());
        } catch (UnableToDeleteFile) {
            // File was probably deleted before
        }
    }

    private function detectMimeType(string $contents): string|null
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: null;
    }
}
