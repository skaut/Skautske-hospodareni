<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Payment;

use Cake\Chronos\Date;
use Helpers;
use Hskauting\Tests\NullEventBus;
use IntegrationTest;
use Model\Payment\EmailType;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\PaymentNotFound;
use Model\Payment\Summary;
use Model\Payment\VariableSymbol;

use function array_fill;
use function array_map;
use function array_merge;
use function assert;

class PaymentRepositoryTest extends IntegrationTest
{
    private const TABLE = 'pa_payment';

    private const PAYMENT_ROW = [
        'group_id' => 1,
        'name' => 'Test',
        'amount' => 200.0,
        'due_date' => '2017-10-29',
        'note' => '',
        'state' => Payment\State::PREPARING,
        'variable_symbol' => '100',
    ];

    private PaymentRepository $repository;

    /** @return string[] */
    public function getTestedAggregateRoots(): array
    {
        return [
            Payment::class,
            Group::class,
            Payment\SentEmail::class,
        ];
    }

    protected function _before(): void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new PaymentRepository($this->entityManager, new NullEventBus());
    }

    public function testFindNotSavedPaymentThrowsException(): void
    {
        $this->expectException(PaymentNotFound::class);

        $this->repository->find(10);
    }

    public function testFind(): void
    {
        $data = self::PAYMENT_ROW;

        $this->addGroupWithId(1);
        $this->addPayments([$data]);

        $payment = $this->repository->find(1);

        $this->assertSame($data['group_id'], $payment->getGroupId());
        $this->assertSame($data['name'], $payment->getName());
        $this->assertSame($data['amount'], $payment->getAmount());
        $this->assertEquals(new Date($data['due_date']), $payment->getDueDate());
        $this->assertTrue($payment->getState()->equalsValue($data['state']), "Payment is not should be 'preparing'");
        $this->assertEquals(new VariableSymbol($data['variable_symbol']), $payment->getVariableSymbol(), 'Variable symbol doesn\'t match');
    }

    public function testFindWithTransaction(): void
    {
        $data = array_merge([
            'transactionId' => '123456',
            'transaction_payer' => 'František Maša',
            'transaction_note' => 'Poznámka',
            'bank_account' => (string) Helpers::createAccountNumber(),
        ], self::PAYMENT_ROW);

        $this->addGroupWithId(1);
        $this->addPayments([$data]);

        $payment = $this->repository->find(1);

        $expectedTransaction = new Payment\Transaction(
            $data['transactionId'],
            $data['bank_account'],
            $data['transaction_payer'],
            $data['transaction_note'],
        );

        $this->assertTrue($expectedTransaction->equals($payment->getTransaction()));
    }

    public function testFindWithSentEmails(): void
    {
        $this->addPayments([self::PAYMENT_ROW]);

        $sentEmail = [
            'payment_id' => 1,
            'time' => '2019-12-24 12:45:41',
            'type' => EmailType::PAYMENT_INFO,
            'sender_name' => 'Petr Svetr',
        ];
        $this->tester->haveInDatabase('pa_payment_sent_emails', $sentEmail);

        $emailRow = $this->repository->find(1)->getSentEmails();

        $this->assertCount(1, $emailRow);
        $this->assertSame($sentEmail['time'], $emailRow[0]->getTime()->format('Y-m-d H:i:s'));
        $this->assertSame($sentEmail['type'], $emailRow[0]->getType()->toString());
        $this->assertSame($sentEmail['sender_name'], $emailRow[0]->getSenderName());
    }

    public function testGetMaxVariableSymbolForNoPaymentIsNull(): void
    {
        $this->assertNull($this->repository->getMaxVariableSymbol(10));
    }

    public function testGetMaxVariableSymbol(): void
    {
        $payments = array_fill(0, 5, self::PAYMENT_ROW);

        $payments[2]['variable_symbol'] = '100';
        $payments[3]['variable_symbol'] = '0100';
        $payments[4]['variable_symbol'] = '1000';

        $this->addGroupWithId(1);
        $this->addPayments($payments);

        $this->assertEquals(new VariableSymbol('1000'), $this->repository->getMaxVariableSymbol(1));
    }

    public function testSummarizeByGroupReturnsCorrectStats(): void
    {
        $payments = [
            [1, 300, Payment\State::PREPARING],
            [1, 300, Payment\State::PREPARING],
            [1, 100, Payment\State::COMPLETED],
            [1, 100, Payment\State::COMPLETED],

            [2, 100, Payment\State::PREPARING],
            [2, 100, Payment\State::PREPARING],
            [2, 300, Payment\State::COMPLETED],
            [2, 300, Payment\State::COMPLETED],
        ];

        $paymentRows = array_map(static function (array $payment): array {
            return [
                'group_id' => $payment[0],
                'name' => 'Test',
                'amount' => $payment[1],
                'due_date' => '2017-10-29',
                'note' => '',
                'state' => $payment[2],
                'variable_symbol' => '100',
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
                Payment\State::COMPLETED => new Summary(2, 200.0),
            ],
            2 => [
                Payment\State::PREPARING => new Summary(2, 200.0),
                Payment\State::COMPLETED => new Summary(2, 600.0),
            ],
        ];

        foreach ($expectedSummaries as $groupId => $summaries) {
            $this->assertCount(2, $result[$groupId], 'There should be 2 items for 2 states');
            foreach ($summaries as $state => $expectedSummary) {
                $actualSummary = $result[$groupId][$state];
                assert($expectedSummary instanceof Summary);
                assert($actualSummary instanceof Summary);

                $this->assertSame($expectedSummary->getCount(), $actualSummary->getCount());
                $this->assertSame($expectedSummary->getAmount(), $actualSummary->getAmount());
            }
        }
    }

    public function testRemove(): void
    {
        $this->addGroupWithId(1);

        $this->tester->haveInDatabase(self::TABLE, [
            'group_id' => 1,
            'name' => 'Test',
            'amount' => 120,
            'due_date' => '2017-10-29',
            'note' => '',
            'state' => Payment\State::PREPARING,
            'variable_symbol' => '100',
        ]);

        $payment = $this->repository->find(1);

        $this->repository->remove($payment);

        $this->tester->dontSeeInDatabase(self::TABLE, ['id' => 1]);
    }

    private function addGroupWithId(int $id): void
    {
        $this->tester->haveInDatabase('pa_group', [
            'id' => $id,
            'name' => 'test',
            'state' => Group::STATE_OPEN,
            'note' => '',
        ]);
    }

    /** @param mixed[] $payments */
    private function addPayments(array $payments): void
    {
        foreach ($payments as $payment) {
            $this->tester->haveInDatabase(self::TABLE, $payment);
        }
    }
}
