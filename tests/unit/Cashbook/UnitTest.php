<?php

declare(strict_types=1);

namespace Model\Cashbook;

use Codeception\Test\Unit as TestCase;
use Model\Cashbook\Cashbook\CashbookId;

use Model\Common\UnitId;

final class UnitTest extends TestCase
{
    public function testCreateWithInitialCashbook() : void
    {
        $id         = new UnitId(15);
        $cashbookId = CashbookId::fromString('123');
        $year       = 2018;

        $unit = new Unit($id, $cashbookId, $year);

        $activeCashbook = $unit->getActiveCashbook();
        $this->assertSame($cashbookId, $activeCashbook->getCashbookId());
        $this->assertSame(1, $activeCashbook->getId());
        $this->assertSame($year, $activeCashbook->getYear());
        $this->assertSame([$activeCashbook], $unit->getCashbooks());
    }
}
