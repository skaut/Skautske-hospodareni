<?php

declare(strict_types=1);

namespace App\Model\Invoice\Entity;

use App\Model\Unit\Unit;
use Codeception\Test\Unit as UnitTest;
use Nette\Utils\ArrayHash;

use function array_keys;

/**
 * Fakturační údaje jednotky — plní se ze Skautisu i z formuláře a přetékají do dodavatele faktury.
 */
final class InvoiceUnitSettingTest extends UnitTest
{
    public function testFromOfficialUnitTakesAddressAndCompanyNumberFromSkautis(): void
    {
        $setting = InvoiceUnitSetting::fromOfficialUnit($this->createUnit(), 2026);

        self::assertSame(123, $setting->getUnit());
        self::assertSame(2026, $setting->getYear());
        self::assertSame('Junák – středisko Test', $setting->getName());
        self::assertSame('Křižíkova 12', $setting->getStreet());
        self::assertSame('Praha', $setting->getCity());
        self::assertSame('18600', $setting->getZipcode());
        self::assertSame('12345678', $setting->getCompanyNumber());
        self::assertNull($setting->getPhone());
        self::assertNull($setting->getStampImagePath());
        self::assertNull($setting->getLogoImagePath());
    }

    public function testFromFormNormalizesEmptyPhoneToNull(): void
    {
        $withPhone = InvoiceUnitSetting::fromForm(123, $this->formValues(['phone' => '+420123456789']));
        $withoutPhone = InvoiceUnitSetting::fromForm(123, $this->formValues(['phone' => '']));

        self::assertSame('+420123456789', $withPhone->getPhone());
        self::assertNull($withoutPhone->getPhone(), 'prázdný telefon se neukládá jako prázdný string');
        self::assertSame(2026, $withPhone->getYear());
    }

    public function testUpdateFromFormOverwritesAllEditableFields(): void
    {
        $setting = InvoiceUnitSetting::fromOfficialUnit($this->createUnit(), 2025);

        $setting->updateFromForm($this->formValues([
            'year' => '2027',
            'name' => 'Nový název',
            'street' => 'Nová 1',
            'city' => 'Brno',
            'zipcode' => '60200',
            'companyNumber' => '87654321',
            'phone' => '  ',
        ]));

        self::assertSame(2027, $setting->getYear());
        self::assertSame('Nový název', $setting->getName());
        self::assertSame('Nová 1', $setting->getStreet());
        self::assertSame('Brno', $setting->getCity());
        self::assertSame('60200', $setting->getZipcode());
        self::assertSame('87654321', $setting->getCompanyNumber());
        self::assertSame('  ', $setting->getPhone(), 'mezery se dnes nezahazují, jen prázdný string');
    }

    public function testToFormValuesRoundTripsThroughUpdateFromForm(): void
    {
        $setting = InvoiceUnitSetting::fromForm(123, $this->formValues(['phone' => '+420111222333']));

        $values = $setting->toFormValues();

        self::assertSame(
            ['year', 'name', 'street', 'city', 'zipcode', 'companyNumber', 'phone'],
            array_keys($values),
        );

        $other = InvoiceUnitSetting::fromOfficialUnit($this->createUnit(), 2000);
        $other->updateFromForm(ArrayHash::from($values));

        self::assertSame($setting->toFormValues(), $other->toFormValues());
    }

    public function testToInvoiceSupplierCopiesBillingData(): void
    {
        $setting = InvoiceUnitSetting::fromForm(123, $this->formValues(['phone' => '+420111222333']));

        $supplier = $setting->toInvoiceSupplier();

        self::assertSame(123, $supplier->getUnitId());
        self::assertSame('Středisko z formuláře', $supplier->getName());
        self::assertSame('11223344', $supplier->getCompanyNumber());
        self::assertSame('+420111222333', $supplier->getPhone());

        $address = $supplier->getAddress();
        self::assertSame('Formulářová 5', $address->getStreet());
        self::assertSame('Ostrava', $address->getCity());
        self::assertSame('70200', $address->getZipCode());
    }

    public function testImagePathsAreSettable(): void
    {
        $setting = InvoiceUnitSetting::fromForm(123, $this->formValues());

        $setting->setStampImagePath('stamps/podpis.png');
        $setting->setLogoImagePath('logos/logo.png');

        self::assertSame('stamps/podpis.png', $setting->getStampImagePath());
        self::assertSame('logos/logo.png', $setting->getLogoImagePath());
    }

    /** @param array<string, mixed> $overrides */
    private function formValues(array $overrides = []): ArrayHash
    {
        return ArrayHash::from($overrides + [
            'year' => '2026',
            'name' => 'Středisko z formuláře',
            'street' => 'Formulářová 5',
            'city' => 'Ostrava',
            'zipcode' => '70200',
            'companyNumber' => '11223344',
            'phone' => null,
        ]);
    }

    private function createUnit(): Unit
    {
        return new Unit(
            123,
            'stredisko test',
            'Junák – středisko Test',
            '12345678',
            'Křižíkova 12',
            'Praha',
            '18600',
            '123.45',
            'stredisko',
        );
    }
}
