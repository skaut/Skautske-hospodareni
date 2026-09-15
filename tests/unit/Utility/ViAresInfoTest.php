<?php

declare(strict_types=1);

namespace Utility\Ares;

use Codeception\Test\Unit;

/**
 * Nositel dat z ARES/VIES — plnitelný settery i polem, poskládá adresu pro fakturu.
 */
final class ViAresInfoTest extends Unit
{
    public function testEmptyInfoStaysEmpty(): void
    {
        $info = new ViAresInfo();

        self::assertTrue($info->isEmpty());
        self::assertNull($info->getVat());
        self::assertNull($info->getCompanyName());
        self::assertNull($info->getCountryCode());
        self::assertFalse($info->isVatPayer());
    }

    public function testConstructorFillsFromArray(): void
    {
        $info = new ViAresInfo([
            'vat' => 'CZ27074358',
            'name' => 'Junák',
            'companyName' => '27074358',
            'street' => 'Senovážné náměstí',
            'streetNumber' => 24,
            'streetNumberSuffix' => 1,
            'vatPayer' => 1,
            'countryCode' => 'CZ',
        ]);

        self::assertFalse($info->isEmpty());
        self::assertSame('CZ27074358', $info->getVat());
        self::assertSame('Junák', $info->getName());
        self::assertSame('27074358', $info->getCompanyName());
        self::assertSame('Senovážné náměstí', $info->getStreet());
        self::assertSame('24', $info->getStreetNumber(), 'čísla se normalizují na string');
        self::assertSame('1', $info->getStreetNumberSuffix());
        self::assertTrue($info->isVatPayer());
        self::assertSame('CZ', $info->getCountryCode());
    }

    public function testFromArrayIgnoresMissingKeys(): void
    {
        $info = (new ViAresInfo(['name' => 'Junák']))->fromArray(['street' => 'Senovážné náměstí']);

        self::assertSame('Junák', $info->getName(), 'co v poli není, zůstane nedotčené');
        self::assertSame('Senovážné náměstí', $info->getStreet());
    }

    public function testSettersAreFluentAndToArrayExportsComputedAddress(): void
    {
        $info = (new ViAresInfo())
            ->setVat('CZ27074358')
            ->setCompanyName('27074358')
            ->setName('Junák')
            ->setStreet('Senovážné náměstí')
            ->setStreetNumber('24')
            ->setStreetNumberSuffix('1')
            ->setCity('Praha')
            ->setZipCode('11000')
            ->setVatPayer(true)
            ->setCountryCode('CZ');

        self::assertSame('Senovážné náměstí 24/1, Praha 11000', $info->getFullAddress());

        self::assertSame([
            'companyName' => '27074358',
            'vat' => 'CZ27074358',
            'name' => 'Junák',
            'street' => 'Senovážné náměstí',
            'streetNumber' => '24',
            'streetNumberSuffix' => '1',
            'city' => 'Praha',
            'zipCode' => '11000',
            'vatPayer' => 1,
            'countryCode' => 'CZ',
            'address' => 'Senovážné náměstí 24/1',
            'fullAddress' => 'Senovážné náměstí 24/1, Praha 11000',
        ], $info->toArray());
    }

    public function testCompanyNameAloneDoesNotMakeInfoNonEmpty(): void
    {
        // isEmpty() se na companyName ani countryCode záměrně nekouká — ARES vrací IČO i pro
        // neexistující subjekt, takže samo o sobě neznamená, že jsme něco našli.
        $info = (new ViAresInfo())->setCompanyName('27074358')->setCountryCode('CZ');

        self::assertTrue($info->isEmpty());
    }

    public function testVatPayerFlagIsExportedAsInteger(): void
    {
        self::assertSame(0, (new ViAresInfo())->toArray()['vatPayer']);
        self::assertSame(1, (new ViAresInfo())->setVatPayer(true)->toArray()['vatPayer']);
    }
}
