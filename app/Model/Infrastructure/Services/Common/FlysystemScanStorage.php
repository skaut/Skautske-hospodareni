<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Services\Common;

use App\Model\Common\Exception\InvalidScanFile;
use App\Model\Common\File;
use App\Model\Common\FilePath;
use App\Model\Common\IScanStorage;
use App\Model\Common\Storage\AbstractStorage;
use finfo;
use League\Flysystem\FilesystemOperator;

use function in_array;

use const FILEINFO_MIME_TYPE;

final class FlysystemScanStorage extends AbstractStorage implements IScanStorage
{
    public function __construct(FilesystemOperator $filesystem)
    {
        parent::__construct($filesystem);
    }

    public function get(FilePath $path): File
    {
        return new File($this->readStreamPath($path->getPath()), $path);
    }

    public function save(FilePath $path, string $contents): void
    {
        $mimeType = $this->detectMimeType($contents);

        if (! in_array($mimeType, IScanStorage::ALLOWED_MIME_TYPES, true)) {
            throw InvalidScanFile::invalidType((string) $mimeType);
        }

        $this->writePath($path->getPath(), $contents);
    }

    public function delete(FilePath $path): void
    {
        $this->deletePath($path->getPath());
    }

    private function detectMimeType(string $contents): ?string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->buffer($contents) ?: null;
    }
}
