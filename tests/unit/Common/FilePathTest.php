<?php

declare(strict_types=1);

namespace Model\Common;

use Codeception\Test\Unit;

final class FilePathTest extends Unit
{
    public function testGenerateUniqPath() : void
    {
        $prefix = '';
        $path   = '';
        $this->assertFalse(FilePath::generate($prefix, $path)->equals(FilePath::generate($prefix, $path)));
    }
}
