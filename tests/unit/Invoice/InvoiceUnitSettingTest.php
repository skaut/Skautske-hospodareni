<?php

declare(strict_types=1);

namespace Entity;

use App\Model\Invoice\Entity\InvoiceUnitSetting;
use Codeception\Test\Unit;

final class InvoiceUnitSettingTest extends Unit
{
    public function testSettingCreatesSupplierSnapshot(): void
    {
        $setting = new InvoiceUnitSetting(
            123,
            2026,
            'Středisko Test',
            'Křižíkova 12',
            'Praha',
            '18600',
            '12345678',
            '+420123456789',
        );

        $supplier = $setting->toInvoiceSupplier();

        self::assertSame(123, $supplier->getUnitId());
        self::assertSame('Středisko Test', $supplier->getName());
        self::assertSame('12345678', $supplier->getCompanyNumber());
        self::assertSame('+420123456789', $supplier->getPhone());
        self::assertSame('Křižíkova 12', $supplier->getAddress()->getStreet());
        self::assertSame('Praha', $supplier->getAddress()->getCity());
        self::assertSame('18600', $supplier->getAddress()->getZipCode());
    }
}
