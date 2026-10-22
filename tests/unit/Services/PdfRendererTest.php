<?php

declare(strict_types=1);

namespace App\Model\Services;

use Codeception\Test\Unit;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tests\Http\RecordingResponse;

use function array_key_exists;
use function ob_get_clean;
use function ob_start;

final class PdfRendererTest extends Unit
{
    private const GotenbergUrl = 'https://gotenberg.example.test/';

    public function testExternalGotenbergRequestUsesBasicAuthentication(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->withArgs(static function (string $method, string $url, array $options): bool {
                return $method === 'POST'
                    && $url === self::GotenbergUrl.'forms/chromium/convert/html'
                    && $options['auth'] === ['gotenberg', 'test-password'];
            })
            ->andReturn(new Response(200, [], '%PDF-1.7'));
        $renderer = $this->createRenderer($client, 'gotenberg', 'test-password');

        self::assertSame('%PDF-1.7', $renderer->renderToString('<p>Výstup</p>'));
    }

    public function testLocalGotenbergRequestDoesNotUseAuthentication(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->withArgs(static function (string $method, string $url, array $options): bool {
                return $method === 'POST'
                    && $url === self::GotenbergUrl.'forms/chromium/convert/html'
                    && ! array_key_exists('auth', $options);
            })
            ->andReturn(new Response(200, [], '%PDF-1.7'));
        $renderer = $this->createRenderer($client);

        self::assertSame('%PDF-1.7', $renderer->renderToString('<p>Výstup</p>'));
    }

    /** @return array<string, array{bool, string}> */
    public static function dispositionProvider(): array
    {
        return [
            'stažení' => [true, 'attachment; filename="report.pdf"; filename*=UTF-8\'\'report.pdf'],
            'otevření' => [false, 'inline; filename="report.pdf"; filename*=UTF-8\'\'report.pdf'],
        ];
    }

    /** @dataProvider dispositionProvider */
    public function testCreatesPdfResponseForDownloadOrOpen(bool $download, string $expectedDisposition): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], '%PDF-1.7'));
        $response = $this->createRenderer($client)->createResponse('<p>Závěrečná zpráva</p>', 'report.pdf', false, $download);
        $httpResponse = new RecordingResponse();

        ob_start();
        $response->send(new Request(new UrlScript('https://moje-hospodareni.cz/')), $httpResponse);
        $body = (string) ob_get_clean();

        self::assertSame('application/pdf', $httpResponse->getHeader('Content-Type'));
        self::assertSame($expectedDisposition, $httpResponse->getHeader('Content-Disposition'));
        self::assertSame('%PDF-1.7', $body);
    }

    private function createRenderer(
        ClientInterface $client,
        ?string $username = null,
        ?string $password = null,
    ): PdfRenderer {
        return new PdfRenderer(
            self::GotenbergUrl,
            __DIR__,
            $client,
            $username,
            $password,
        );
    }
}
