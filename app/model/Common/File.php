<?php

declare(strict_types=1);

namespace Model\Common;

use Psr\Http\Message\StreamInterface;

use function array_key_last;
use function basename;
use function explode;

final class File
{
    public function __construct(private StreamInterface $stream, private FilePath $path)
    {
    }

    public function getPath(): string
    {
        return $this->path->getPath();
    }

    public function getFileName(): string
    {
        return basename($this->path->getPath());
    }

    public function getOriginalFileName(): string
    {
        $arr = explode('_', $this->getFileName(), 2);

        return $arr[array_key_last($arr)];
    }

    public function getContents(): StreamInterface
    {
        return $this->stream;
    }
}
