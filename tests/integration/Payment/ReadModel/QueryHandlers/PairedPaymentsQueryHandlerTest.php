<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use DateTimeImmutable;
use IntegrationTest;
use Model\Payment\BankAccount\BankAccountId;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\ReadModel\Queries\PairedPaymentsQuery;

final class PairedPaymentsQueryHandlerTest extends IntegrationTest
{
    /**
     * @return string[]
     */
    protected function getTestedAggregateRoots() : array
    {
        return [
            Payment::class,
            Group::class,
        ];
    }

    public function testHandlerReturnsCorrectData() : void
    {
        $group = [
            'label' => 'Test',
            'bank_account_id' => 10,
            'last_pairing' => '2018-10-01 15:30:00',
            'state' => Group::STATE_CLOSED,
            'state_info' => '',
        ];

        $this->tester->haveInDatabase('pa_group', $group); // Group #1
        $this->tester->haveInDatabase('pa_group', $group); // Group #2

        $inRange    = new DateTimeImmutable('2018-09-25 00:00:00');
        $outOfRange = new DateTimeImmutable('2018-03-20 00:00:00');

        $this->insertPaymentToDb(1, '123', $inRange); // Payment #1
        $this->insertPaymentToDb(1, '123', $outOfRange);
        $this->insertPaymentToDb(1, null, $inRange);
        $this->insertPaymentToDb(2, null, $outOfRange);
        $this->insertPaymentToDb(2, '345', $inRange); // Payment #5

        $handler = new PairedPaymentsQueryHandler($this->entityManager);

        $payments = $handler(
            new PairedPaymentsQuery(
                new BankAccountId(10),
                new DateTimeImmutable('2018-09-24 00:00:00'),
                new DateTimeImmutable('2018-10-01 00:00:00')
            )
        );

        $this->assertCount(2, $payments);
        $this->assertSame(1, $payments[0]->getId());
        $this->assertSame(5, $payments[1]->getId());
    }

    private function insertPaymentToDb(int $groupId, ?string $transactionId, DateTimeImmutable $closedAt) : void
    {
        $payment = [
            'name' => 'Test',
            'amount' => 150,
            'maturity' => '2017-10-29',
            'state' => Payment\State::COMPLETED,
            'groupId' => $groupId,
            'transactionId' => $transactionId,
            'transaction_payer' => $transactionId !== null ? 'A' : null,
            'transaction_note' => $transactionId !== null ? 'A' : null,
            'dateClosed' => $closedAt->format('Y-m-d H:i:s'),
            'note' => '',
        ];

        $this->tester->haveInDatabase('pa_payment', $payment);
    }
}
