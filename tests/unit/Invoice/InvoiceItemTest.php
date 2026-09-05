<?php

declare(strict_types=1);

namespace Entity;

use App\Model\Invoice\Entity\InvoiceItem;
use Brick\Math\BigDecimal;
use Codeception\Test\Unit;

final class InvoiceItemTest extends Unit
{
    public function testTotalPriceIsCalculatedFromUnitPriceAndQuantity(): void
    {
        $item = new InvoiceItem(BigDecimal::of('121.00'), 'Služba', 2, 'ks');

        self::assertSame('242.00', (string) $item->getTotalPrice());
    }

    public function testDecimalUnitPriceKeepsCentsExact(): void
    {
        $item = new InvoiceItem(BigDecimal::of('0.10'), 'Služba', 3, 'ks');

        self::assertSame('0.30', (string) $item->getTotalPrice());
    }
}
