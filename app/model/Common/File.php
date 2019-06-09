<?php

declare(strict_types=1);

namespace Model\Common;

use Psr\Http\Message\StreamInterface;
use function basename;

final class File
{
    /** @var StreamInterface */
    private $stream;

    /** @var string */
    private $path;

    public function __construct(StreamInterface $stream, string $path)
    {
        $this->stream = $stream;
        $this->path   = $path;
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
        return $this->stream;
    }
}
