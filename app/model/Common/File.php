<?php

declare(strict_types=1);

namespace Model\Common;

use Psr\Http\Message\StreamInterface;
use function basename;

final class File
{
    /** @var StreamInterface */
    private $stream;

    /** @var FilePath */
    private $path;

    public function __construct(StreamInterface $stream, FilePath $path)
    {
        $this->stream = $stream;
        $this->path   = $path;
    }

    public function getPath() : string
    {
        return $this->path->getPath();
    }

    public function getFileName() : string
    {
        return basename($this->path->getPath());
    }

    public function getContents() : StreamInterface
    {
        return $this->stream;
    }
}
