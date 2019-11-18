<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Cake\Chronos\Date;
use eGen\MessageBus\Bus\EventBus;
use Helpers;
use IntegrationTest;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\PaymentNotFound;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;
use function array_fill;
use function array_map;
use function array_merge;

class PaymentRepositoryTest extends IntegrationTest
{
    private const TABLE = 'pa_payment';

    private const PAYMENT_ROW = [
        'groupId' => 1,
        'name' => 'Test',
        'email' => 'frantisekmasa1@gmail.com',
        'amount' => 200.0,
        'maturity' => '2017-10-29',
        'note' => '',
        'state' => Payment\State::PREPARING,
        'vs' => '100',
    ];

    /** @var PaymentRepository */
    private $repository;

    /**
     * @return string[]
     */
    public function getTestedEntites() : array
    {
        return [
            Payment::class,
            Group::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new PaymentRepository($this->entityManager, new EventBus());
    }

    public function testFindNotSavedPaymentThrowsException() : void
    {
        $this->expectException(PaymentNotFound::class);

        $this->repository->find(10);
    }

    public function testFind() : void
    {
        $data = self::PAYMENT_ROW;

        $this->addGroupWithId(1);
        $this->addPayments([$data]);

        $payment = $this->repository->find(1);

        $this->assertSame($data['groupId'], $payment->getGroupId());
        $this->assertSame($data['name'], $payment->getName());
        $this->assertSame($data['email'], $payment->getEmail());
        $this->assertSame($data['amount'], $payment->getAmount());
        $this->assertEquals(new Date($data['maturity']), $payment->getDueDate());
        $this->assertTrue($payment->getState()->equalsValue($data['state']), "Payment is not should be 'preparing'");
        $this->assertEquals(new VariableSymbol($data['vs']), $payment->getVariableSymbol(), 'Variable symbol doesn\'t match');
    }

    public function testFindWithTransaction() : void
    {
        $data = array_merge([
            'transactionId' => '123456',
            'transaction_payer' => 'František Maša',
            'transaction_note' => 'Poznámka',
            'paidFrom' => (string) Helpers::createAccountNumber(),
        ], self::PAYMENT_ROW);

        $this->addGroupWithId(1);
        $this->addPayments([$data]);

        $payment = $this->repository->find(1);

        $expectedTransaction = new Payment\Transaction(
            $data['transactionId'],
            $data['paidFrom'],
            $data['transaction_payer'],
            $data['transaction_note']
        );

        $this->assertTrue($expectedTransaction->equals($payment->getTransaction()));
    }

    public function testGetMaxVariableSymbolForNoPaymentIsNull() : void
    {
        $this->assertNull($this->repository->getMaxVariableSymbol(10));
    }

    public function testGetMaxVariableSymbol() : void
    {
        $payments = array_fill(0, 5, self::PAYMENT_ROW);

        $payments[2]['vs'] = '100';
        $payments[3]['vs'] = '0100';
        $payments[4]['vs'] = '1000';

        $this->addGroupWithId(1);
        $this->addPayments($payments);

        $this->assertEquals(new VariableSymbol('1000'), $this->repository->getMaxVariableSymbol(1));
    }

    public function testSummarizeByGroupReturnsCorrectStats() : void
    {
        $payments = [
            [1, 300, Payment\State::PREPARING],
            [1, 300, Payment\State::PREPARING],
            [1, 200, Payment\State::SENT],
            [1, 200, Payment\State::SENT],
            [1, 100, Payment\State::COMPLETED],
            [1, 100, Payment\State::COMPLETED],

            [2, 100, Payment\State::PREPARING],
            [2, 100, Payment\State::PREPARING],
            [2, 200, Payment\State::SENT],
            [2, 200, Payment\State::SENT],
            [2, 300, Payment\State::COMPLETED],
            [2, 300, Payment\State::COMPLETED],
        ];

        $paymentRows = array_map(static function (array $payment) : array {
            return [
                'groupId' => $payment[0],
                'name' => 'Test',
                'email' => 'frantisekmasa1@gmail.com',
                'amount' => $payment[1],
                'maturity' => '2017-10-29',
                'note' => '',
                'state' => $payment[2],
                'vs' => '100',
            ];
        }, $payments);

        $this->addGroupWithId(1);
        $this->addGroupWithId(2);
        $this->addPayments($paymentRows);

        $result = $this->repository->summarizeByGroup([1, 2]);

        $this->assertCount(2, $result, 'There should be two items for two groups');

        $expectedSummaries = [
            1 => [
                Payment\State::PREPARING => new Summary(2, 600.0),
                Payment\State::SENT => new Summary(2, 400.0),
                Payment\State::COMPLETED => new Summary(2, 200.0),
            ],
            2 => [
                Payment\State::PREPARING => new Summary(2, 200.0),
                Payment\State::SENT => new Summary(2, 400.0),
                Payment\State::COMPLETED => new Summary(2, 600.0),
            ],
        ];

        foreach ($expectedSummaries as $groupId => $summaries) {
            $this->assertCount(3, $result[$groupId], 'There should be 3 items for 3 states');
            foreach ($summaries as $state => $expectedSummary) {
                $actualSummary = $result[$groupId][$state];
                /** @var $expectedSummary Summary */
                /** @var Summary $actualSummary */

                $this->assertSame($expectedSummary->getCount(), $actualSummary->getCount());
                $this->assertSame($expectedSummary->getAmount(), $actualSummary->getAmount());
            }
        }
    }

    public function testRemove() : void
    {
        $this->addGroupWithId(1);

        $this->tester->haveInDatabase(self::TABLE, [
            'groupId' => 1,
            'name' => 'Test',
            'email' => 'frantisekmasa1@gmail.com',
            'amount' => 120,
            'maturity' => '2017-10-29',
            'note' => '',
            'state' => Payment\State::SENT,
            'vs' => '100',
        ]);

        $payment = $this->repository->find(1);

        $this->repository->remove($payment);

        $this->tester->dontSeeInDatabase(self::TABLE, ['id' => 1]);
    }

    private function addGroupWithId(int $id) : void
    {
        $this->tester->haveInDatabase('pa_group', [
            'id' => $id,
            'label' => 'test',
            'state' => Group::STATE_OPEN,
            'state_info' => '',
        ]);
    }

    /**
     * @param mixed[] $payments
     */
    private function addPayments(array $payments) : void
    {
        foreach ($payments as $payment) {
            $this->tester->haveInDatabase(self::TABLE, $payment);
        }
    }
}
