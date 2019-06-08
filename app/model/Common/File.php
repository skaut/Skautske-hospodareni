<?php

declare(strict_types=1);

namespace Model\Common;

use Psr\Http\Message\StreamInterface;
use function basename;
use function fopen;
use function GuzzleHttp\Psr7\stream_for;

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

    public function getFileName() : string
    {
        return basename($this->path);
    }

    public function getContents() : StreamInterface
    {
        return stream_for(fopen($this->fullPath, 'rb'));
    }
}
