<?php

declare(strict_types=1);

namespace Utility\Ares;

use Codeception\Test\Unit;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;

final class ViAresParserTest extends Unit
{
    public function testAresResponseWithNumericIcoIsParsed(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/27074358')
            ->andReturn(new Response(200, [], <<<'JSON'
{"ico":27074358,"obchodniJmeno":"Junak","sidlo":{"nazevUlice":"Senovazne namesti","cisloDomovni":24,"cisloOrientacni":1,"nazevObce":"Praha","psc":"11000"},"dic":"CZ27074358"}
JSON
            ));

        $parser = new ViAresParser($client);
        $info = $parser->getAres('270 74 358');

        self::assertSame('27074358', $info->getCompanyName());
        self::assertSame('Junak', $info->getName());
        self::assertSame('Senovazne namesti', $info->getStreet());
        self::assertSame('24', $info->getStreetNumber());
        self::assertSame('1', $info->getStreetNumberSuffix());
        self::assertSame('Praha', $info->getCity());
        self::assertSame('11000', $info->getZipCode());
        self::assertSame('CZ27074358', $info->getVat());
        self::assertFalse($info->isEmpty());
    }

    public function testAresResponseWithDifferentIcoReturnsEmptyInfo(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/27074358')
            ->andReturn(new Response(200, [], '{"ico":12345678}'));

        $parser = new ViAresParser($client);

        self::assertTrue($parser->getAres('27074358')->isEmpty());
    }
}
