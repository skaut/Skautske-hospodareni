<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Utils\MoneyFactory;
use Codeception\Test\Unit;

final class SummaryTest extends Unit
{
    public function testAddingSummariesKeepsCentsExact(): void
    {
        $summary = new Summary(1, MoneyFactory::fromDecimal('0.10'));
        $result = $summary->add(new Summary(2, MoneyFactory::fromDecimal('0.20')));

        self::assertSame(3, $result->getCount());
        self::assertSame('30', $result->getAmount()->getAmount());
    }
}
