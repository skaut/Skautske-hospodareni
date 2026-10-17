<?php

declare(strict_types=1);

namespace App\Model\Common;

use Codeception\Test\Unit;

final class FilePathTest extends Unit
{
    public function testGenerateUniqPath(): void
    {
        $prefix = '';
        $path = '';
        $this->assertFalse(FilePath::generate($prefix, $path)->equals(FilePath::generate($prefix, $path)));
    }

    /** @return array<string, array{string, string, bool}> */
    public static function contentTypeProvider(): array
    {
        return [
            'pdf' => ['chits/2026/10/6512ab_uctenka.pdf', 'application/pdf', true],
            'pdf velkými písmeny' => ['chits/2026/10/6512ab_UCTENKA.PDF', 'application/pdf', true],
            'jpg' => ['chits/2026/10/6512ab_uctenka.jpg', 'image/jpeg', false],
            'png' => ['roadworthy/6512ab_prukaz.png', 'image/png', false],
            'neznámá přípona' => ['chits/2026/10/6512ab_uctenka.pdf.html', 'application/octet-stream', false],
            'bez přípony' => ['chits/2026/10/6512ab_uctenka', 'application/octet-stream', false],
        ];
    }

    /** @dataProvider contentTypeProvider */
    public function testContentTypeIsDerivedFromExtension(string $path, string $contentType, bool $isPdf): void
    {
        $filePath = FilePath::fromString($path);

        $this->assertSame($contentType, $filePath->getContentType());
        $this->assertSame($isPdf, $filePath->isPdf());
    }
}
