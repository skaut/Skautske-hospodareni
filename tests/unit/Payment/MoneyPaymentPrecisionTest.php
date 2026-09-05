<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Common\EmailAddress;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use InvalidArgumentException;
use Mockery as m;

final class MoneyPaymentPrecisionTest extends Unit
{
    public function testSplitRetainsExactRemainingCents(): void
    {
        $payment = $this->createPayment('0.30');

        $payment->reduceAmountBySplit(MoneyFactory::fromDecimal('0.19'));

        self::assertSame('11', $payment->getAmount()->getAmount());
        self::assertSame('0.11', MoneyFactory::toDecimal($payment->getAmount()));
    }

    public function testSplitRejectsOneCentMoreThanOriginalAmount(): void
    {
        $payment = $this->createPayment('0.30');

        $this->expectException(InvalidArgumentException::class);
        $payment->reduceAmountBySplit(MoneyFactory::fromDecimal('0.31'));
    }

    private function createPayment(string $amount): Payment
    {
        $group = m::mock(Group::class);
        $group->shouldReceive('getId')->andReturn(1);

        return new Payment(
            $group,
            'Účastnický poplatek',
            [new EmailAddress('participant@example.test')],
            MoneyFactory::fromDecimal($amount),
            ChronosDate::today(),
            null,
            null,
            null,
            '',
        );
    }
}
