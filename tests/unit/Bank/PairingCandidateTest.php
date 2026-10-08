<?php

declare(strict_types=1);

namespace App\Model\Bank;

use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\VariableSymbol;
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use Mockery as m;

final class PairingCandidateTest extends Unit
{
    public function testPaymentMatchKeyUsesExactMinorUnits(): void
    {
        $group = m::mock(Group::class);
        $group->shouldReceive('getId')->andReturn(1);
        $payment = new Payment(
            $group,
            'Platba',
            [],
            MoneyFactory::fromDecimal('0.19'),
            ChronosDate::today(),
            new VariableSymbol('123456'),
            null,
            null,
            '',
        );

        self::assertSame('123456|19', PairingCandidate::forPayment($payment)->getMatchKey());
    }
}
