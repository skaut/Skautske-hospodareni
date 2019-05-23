<?php

declare(strict_types=1);

namespace Model\Common;

use finfo;
use Nette\Utils\FileSystem;
use const FILEINFO_MIME_TYPE;

final class File
{
    /** @var string */
    private $fullPath;

    /** @var string */
    private $path;

    public function __construct(string $fullPath, string $path)
    {
        $this->fullPath = $fullPath;
        $this->path     = $path;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getMimeType() : string
    {
        return (new finfo(FILEINFO_MIME_TYPE))->file($this->fullPath);
    }

    public function getContents() : string
    {
        return FileSystem::read($this->fullPath);
    }
}
