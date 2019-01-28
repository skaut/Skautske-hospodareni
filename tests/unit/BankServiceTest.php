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

    public function testPaymentIsPairedOnlyByJoinedAccount() : void
    {
        $groupId1       = 12;
        $bankAccountId1 = 159;
        $lastPairing    = new \DateTimeImmutable('- 5 days');

        $group1 = m::mock(Group::class, [
            'getId' => $groupId1,
            'getBankAccountId' => $bankAccountId1,
            'getLastPairing' => $lastPairing,
            'updateLastPairing' => null,
        ]);

        $groupId2       = 34;
        $bankAccountId2 = 357;

        $group2 = m::mock(Group::class, [
            'getId' => $groupId2,
            'getBankAccountId' => $bankAccountId2,
            'getLastPairing' => $lastPairing,
            'updateLastPairing' => null,
        ]);

        $groups = m::mock(IGroupRepository::class);
        $groups->shouldReceive('findByIds')
            ->once()
            ->with([$groupId1, $groupId2])
            ->andReturn([$groupId1 => $group1, $groupId2 => $group2]);
        $groups->shouldReceive('save');

        $bankAccount1 = m::mock(BankAccount::class, ['getToken' => '123']);
        $bankAccount2 = m::mock(BankAccount::class, ['getToken' => '852']);

        $bankAccounts = m::mock(IBankAccountRepository::class);
        $bankAccounts->shouldReceive('find')
            ->once()
            ->with($bankAccountId1)
            ->andReturn($bankAccount1);
        $bankAccounts->shouldReceive('find')
            ->once()
            ->with($bankAccountId2)
            ->andReturn($bankAccount2);

        $today    = new \DateTimeImmutable();
        $amount   = 200.50;
        $vs1      = new VariableSymbol('123456');
        $account  = (string) \Helpers::createAccountNumber();
        $vs2      = new VariableSymbol('7854');
        $account2 = (string) new BankAccount\AccountNumber('19', '2000145399', '0800');

        $transactions1 = [
            new Transaction('123', $today, $amount, $account, 'František Maša', $vs1->toInt(), null, null),
        ];

        $transactions2 = [
            new Transaction('456', $today, $amount, $account2, 'František Maša', $vs2->toInt(), null, null),
        ];
        $bank          = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions1);

        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions2);

        $payments1 = [
            new Payment($group1, '-', null, $amount, new \DateTimeImmutable(), $vs1, null, null, ''),
        ];
        \Helpers::assignIdentity($payments1[0], 1);

        $payments2 = [
            new Payment($group2, '-', null, $amount, new \DateTimeImmutable(), $vs2, null, null, ''),
        ];
        \Helpers::assignIdentity($payments2[0], 2);

        $paymentRepository = m::mock(IPaymentRepository::class);
        $paymentRepository->shouldReceive('findByMultipleGroups')
            ->once()
            ->with([$groupId1])
            ->andReturn($payments1);
        $paymentRepository->shouldReceive('findByMultipleGroups')
            ->once()
            ->with([$groupId2])
            ->andReturn($payments2);
        $paymentRepository->shouldReceive('saveMany');

        $bankService = new BankService($groups, $bank, $paymentRepository, $bankAccounts);

        $paired = $bankService->pairAllGroups([$groupId1, $groupId2]);
        $this->assertSame(2, $paired);
    }
}
