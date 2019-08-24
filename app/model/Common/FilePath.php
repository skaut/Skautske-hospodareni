<?php

declare(strict_types=1);

namespace Model\Common;

use Cake\Chronos\Date;

final class FilePath
{
    /** @var string */
    private $path;

    public function __construct(string $prefix, string $path)
    {
        $this->path = $this->generatePath($prefix, $path);
    }

    public function getPath() : string
    {
        return $this->path;
    }

    private function generatePath(string $prefix, string $originalFileName) : string
    {
        return $prefix . '/' . Date::today()->format('Y/m') . '/' . $originalFileName;
    }
}
