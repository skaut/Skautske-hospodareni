<?php

declare(strict_types=1);

namespace App\Model\Services;

use Codeception\Test\Unit;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;

use function array_key_exists;

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
