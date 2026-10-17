<?php

declare(strict_types=1);

namespace App\Http;

use Codeception\Test\Unit;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tests\Http\RecordingResponse;

use function ob_get_clean;
use function ob_start;
use function strlen;

final class PdfResponseTest extends Unit
{
    private const Pdf = "%PDF-1.7\n%%EOF";

    public function testDownloadIsSentAsAttachment(): void
    {
        [$headers, $body] = $this->send(new PdfResponse(self::Pdf, 'faktura-2026-001.pdf', true));

        self::assertSame('application/pdf', $headers->getHeader('Content-Type'));
        self::assertSame(
            'attachment; filename="faktura-2026-001.pdf"; filename*=UTF-8\'\'faktura-2026-001.pdf',
            $headers->getHeader('Content-Disposition'),
        );
        self::assertSame((string) strlen(self::Pdf), $headers->getHeader('Content-Length'));
        self::assertSame('nosniff', $headers->getHeader('X-Content-Type-Options'));
        self::assertSame('no-store, max-age=0', $headers->getHeader('Cache-Control'));
        self::assertSame(self::Pdf, $body);
    }

    public function testOpenIsSentInline(): void
    {
        [$headers, $body] = $this->send(new PdfResponse(self::Pdf, 'cestovni-prikaz.pdf'));

        self::assertSame('application/pdf', $headers->getHeader('Content-Type'));
        self::assertSame(
            'inline; filename="cestovni-prikaz.pdf"; filename*=UTF-8\'\'cestovni-prikaz.pdf',
            $headers->getHeader('Content-Disposition'),
        );
        self::assertSame(self::Pdf, $body);
    }

    /** @return array{RecordingResponse, string} */
    private function send(PdfResponse $response): array
    {
        $httpResponse = new RecordingResponse();

        ob_start();
        $response->send(new Request(new UrlScript('https://moje-hospodareni.cz/')), $httpResponse);

        return [$httpResponse, (string) ob_get_clean()];
    }
}
