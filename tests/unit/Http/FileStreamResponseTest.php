<?php

declare(strict_types=1);

namespace App\Http;

use Codeception\Test\Unit;
use GuzzleHttp\Psr7\Utils;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tests\Http\RecordingResponse;

use function ob_get_clean;
use function ob_start;
use function strlen;

final class FileStreamResponseTest extends Unit
{
    private const Pdf = "%PDF-1.4\n%%EOF";

    public function testPdfAttachmentCanBeOpenedInline(): void
    {
        [$headers, $body] = $this->send(new FileStreamResponse(Utils::streamFor(self::Pdf), '6512ab_uctenka.pdf', 'application/pdf', false));

        self::assertSame('application/pdf', $headers->getHeader('Content-Type'));
        self::assertSame(
            'inline; filename="6512ab_uctenka.pdf"; filename*=UTF-8\'\'6512ab_uctenka.pdf',
            $headers->getHeader('Content-Disposition'),
        );
        self::assertSame((string) strlen(self::Pdf), $headers->getHeader('Content-Length'));
        self::assertSame('nosniff', $headers->getHeader('X-Content-Type-Options'));
        self::assertSame(self::Pdf, $body);
    }

    public function testPdfAttachmentIsDownloadedByDefault(): void
    {
        [$headers] = $this->send(new FileStreamResponse(Utils::streamFor(self::Pdf), '6512ab_uctenka.pdf', 'application/pdf'));

        self::assertSame(
            'attachment; filename="6512ab_uctenka.pdf"; filename*=UTF-8\'\'6512ab_uctenka.pdf',
            $headers->getHeader('Content-Disposition'),
        );
    }

    public function testOtherFilesAreNeverSentInline(): void
    {
        $response = new FileStreamResponse(Utils::streamFor('<svg onload="alert(1)"/>'), 'sken.png', 'image/png', false);
        [$headers] = $this->send($response);

        self::assertFalse($response->isInline());
        self::assertSame('image/png', $headers->getHeader('Content-Type'));
        self::assertSame(
            'attachment; filename="sken.png"; filename*=UTF-8\'\'sken.png',
            $headers->getHeader('Content-Disposition'),
        );
        self::assertSame('nosniff', $headers->getHeader('X-Content-Type-Options'));
    }

    public function testFilenameCannotBreakHeader(): void
    {
        [$headers] = $this->send(new FileStreamResponse(Utils::streamFor(self::Pdf), "účtenka \"září\"\r\nX-Evil: 1.pdf", 'application/pdf'));

        self::assertSame(
            'attachment; filename="uctenka _zari_X-Evil: 1.pdf"; filename*=UTF-8\'\'%C3%BA%C4%8Dtenka%20%22z%C3%A1%C5%99%C3%AD%22X-Evil%3A%201.pdf',
            $headers->getHeader('Content-Disposition'),
        );
    }

    /** @return array{RecordingResponse, string} */
    private function send(FileStreamResponse $response): array
    {
        $httpResponse = new RecordingResponse();

        ob_start();
        $response->send(new Request(new UrlScript('https://moje-hospodareni.cz/')), $httpResponse);

        return [$httpResponse, (string) ob_get_clean()];
    }
}
