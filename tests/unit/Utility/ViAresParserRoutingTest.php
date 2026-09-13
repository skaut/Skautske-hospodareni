<?php

declare(strict_types=1);

namespace Utility\Ares;

use Codeception\Test\Unit;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Mockery;
use RuntimeException;
use ValueError;

/**
 * Doplňuje ViAresParserTest: směrování CZ → ARES vs. zahraniční → VIES a validace vstupu.
 */
final class ViAresParserRoutingTest extends Unit
{
    public function testCzechCompanyNumberGoesToAres(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/27074358')
            ->andReturn(new Response(200, [], '{"ico":27074358,"obchodniJmeno":"Junák"}'));

        $info = (new ViAresParser($client))->getViAresInfo('CZ27074358');

        self::assertSame('Junák', $info->getName());
        self::assertFalse($info->isVatPayer(), 'bez DIČ není plátcem DPH');
        self::assertNull($info->getVat());
    }

    public function testLowercaseCountryCodeIsNormalized(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/27074358')
            ->andReturn(new Response(200, [], '{"ico":27074358}'));

        self::assertTrue((new ViAresParser($client))->getViAresInfo('cz27074358')->isEmpty());
    }

    public function testAresResponseWithoutAddressIsStillParsed(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], '{"ico":27074358,"obchodniJmeno":"Junák","sidlo":"neplatné","dic":"CZ27074358"}'));

        $info = (new ViAresParser($client))->getAres('27074358');

        self::assertSame('Junák', $info->getName());
        self::assertNull($info->getCity(), 'nečekaný tvar sídla se ignoruje');
        self::assertTrue($info->isVatPayer());
    }

    public function testForeignVatNumberGoesToVies(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->with('GET', 'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/SK/vat/2020123456')
            ->andReturn(new Response(200, [], '{"isValid":true,"name":"Slovenský Junák","address":"Bratislava 1","vatNumber":"2020123456"}'));

        $info = (new ViAresParser($client))->getViAresInfo('SK2020123456');

        self::assertSame('Slovenský Junák', $info->getName());
        self::assertSame('2020123456', $info->getVat());
        self::assertSame('SK', $info->getCountryCode());
        self::assertFalse($info->isVatPayer());
    }

    public function testViesRejectsInvalidVatNumber(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->andReturn(new Response(200, [], '{"isValid":false}'));

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Data from VIES are not valid.');

        (new ViAresParser($client))->getVies('2020123456', 'SK');
    }

    public function testViesRejectsNonOkResponse(): void
    {
        $client = Mockery::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->once()
            ->andReturn(new Response(404, [], '{}'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Response error code :404');

        (new ViAresParser($client))->getVies('2020123456', 'SK');
    }

    /** @dataProvider provideInvalidInputs */
    public function testInputValidation(string $input, string $expectedMessage): void
    {
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage($expectedMessage);

        (new ViAresParser(Mockery::mock(ClientInterface::class)))->getViAresInfo($input);
    }

    /** @return array<string, array{string, string}> */
    public function provideInvalidInputs(): array
    {
        $rangeMessage = 'Počet číslic v IČO je mimo rozsah. Přípustný rozsah je 8 až 12 číslic.';

        return [
            'bez předvolby země' => ['27074358', 'První dva znaky nejsou písmena.'],
            'jen jedno písmeno' => ['C7074358', 'První dva znaky nejsou písmena.'],
            'příliš krátké IČO' => ['CZ2707435', $rangeMessage],
            'příliš dlouhé IČO' => ['CZ2707435812345', $rangeMessage],  // 12 číslic ještě projde, 13 už ne
            'prázdný vstup' => ['', 'První dva znaky nejsou písmena.'],
        ];
    }
}
