<?php

declare(strict_types=1);

namespace Model;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Bank\Fio\Transaction;
use Model\Payment\BankAccount;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\VariableSymbol;
use function array_map;

final class BankServiceTest extends Unit
{
    /**
     * @see https://github.com/skaut/Skautske-hospodareni/pull/508
     */
    public function testPaymentIsPairedOnlyOnceForDuplicateTransactions() : void
    {
        $groupId       = 123;
        $bankAccountId = 456;

        $group = m::mock(Group::class, [
            'getId' => $groupId,
            'getBankAccountId' => $bankAccountId,
            'getLastPairing' => new \DateTimeImmutable('- 5 days'),
            'updateLastPairing' => null,
        ]);

        $groups = m::mock(IGroupRepository::class);
        $groups->shouldReceive('findByIds')
            ->once()
            ->with([$groupId])
            ->andReturn([$groupId => $group]);
        $groups->shouldReceive('save');

        $bankAccount = m::mock(BankAccount::class, ['getToken' => '123']);

        $bankAccounts = m::mock(IBankAccountRepository::class);
        $bankAccounts->shouldReceive('find')
            ->once()
            ->with($bankAccountId)
            ->andReturn($bankAccount);

        $amount  = 200.50;
        $vs      = new VariableSymbol('123456');
        $account = (string) \Helpers::createAccountNumber();

        $transactions = array_map(
            function (string $id) use ($amount, $vs, $account) {
                $today = new \DateTimeImmutable();

                return new Transaction($id, $today, $amount, $account, 'František Maša', $vs->toInt(), null, 'note' . $id);
            },
            ['123', '456']
        );

        $bank = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions);

        $payments = [
            new Payment($group, '-', null, $amount, new \DateTimeImmutable(), $vs, null, null, ''),
        ];

        \Helpers::assignIdentity($payments[0], 1);

        $paymentRepository = m::mock(IPaymentRepository::class);
        $paymentRepository->shouldReceive('findByMultipleGroups')
            ->once()
            ->with([$groupId])
            ->andReturn($payments);
        $paymentRepository->shouldReceive('saveMany');

        $bankService = new BankService($groups, $bank, $paymentRepository, $bankAccounts);

        $bankService->pairAllGroups([$groupId]);

        $transaction = $payments[0]->getTransaction();
        $this->assertSame(123, $transaction->getId());
        $this->assertSame($account, $transaction->getBankAccount());
        $this->assertSame('note123', $transaction->getNote());
        $this->assertSame('František Maša', $transaction->getPayer());
    }
}
