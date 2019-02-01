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
use function array_keys;
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

        $group  = $this->mockGroup($groupId, $bankAccountId, new \DateTimeImmutable('- 5 days'));
        $groups = $this->mockGroupRepository([$groupId => $group]);

        $bankAccount = $this->mockBankAccount('123');

        $bankAccounts = $this->mockBankAccountRepository([$bankAccountId => $bankAccount]);

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

        $paymentRepository = $this->mockPaymentRepository([$groupId => $payments]);

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

        $group1 = $this->mockGroup($groupId1, $bankAccountId1, $lastPairing);

        $groupId2       = 34;
        $bankAccountId2 = 357;

        $group2 = $this->mockGroup($groupId2, $bankAccountId2, $lastPairing);

        $groups = $this->mockGroupRepository([$groupId1 => $group1, $groupId2 => $group2]);

        $bankAccount1 = $this->mockBankAccount('123');
        $bankAccount2 = $this->mockBankAccount('852');

        $bankAccounts = $this->mockBankAccountRepository([
            $bankAccountId1 => $bankAccount1,
            $bankAccountId2 => $bankAccount2,
        ]);

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
        
        $payments1 = [
            new Payment($group1, '-', null, $amount, new \DateTimeImmutable(), $vs1, null, null, ''),
            new Payment($group2, '-', null, $amount, new \DateTimeImmutable(), $vs2, null, null, ''),
        ];
        \Helpers::assignIdentity($payments1[0], 1);

        $payments2 = [];

        $paymentRepository = $this->mockPaymentRepository([
            $groupId1 => $payments1,
            $groupId2 => $payments2,
        ]);

        $bankService = new BankService($groups, $bank, $paymentRepository, $bankAccounts);

        $paired = $bankService->pairAllGroups([$groupId1, $groupId2]);
        $this->assertSame(1, $paired);
    }

    /**
     * @param BankAccount[] $bankAccounts
     */
    public function mockBankAccountRepository(array $bankAccounts) : IBankAccountRepository
    {
        $repository = m::mock(IBankAccountRepository::class);
        foreach ($bankAccounts as $bankAccountId => $bankAccount) {
            $repository->shouldReceive('find')
                ->once()
                ->with($bankAccountId)
                ->andReturn($bankAccount);
        }
        return $repository;
    }

    /**
     * @param Group[] $groups
     */
    private function mockGroupRepository(array $groups) : IGroupRepository
    {
        $repository = m::mock(IGroupRepository::class);
        $repository->shouldReceive('findByIds')
            ->once()
            ->with(array_keys($groups))
            ->andReturn($groups);
        $repository->shouldReceive('save');
        return $repository;
    }

    private function mockBankAccount(string $token) : BankAccount
    {
        return m::mock(BankAccount::class, ['getToken' => $token]);
    }

    /**
     * @param Payment[] $paymentsByGroup
     */
    private function mockPaymentRepository(array $paymentsByGroup) : IPaymentRepository
    {
        $repository = m::mock(IPaymentRepository::class);
        foreach ($paymentsByGroup as $groupId => $payments) {
            $repository->shouldReceive('findByMultipleGroups')
                ->once()
                ->with([$groupId])
                ->andReturn($payments);
        }
        $repository->shouldReceive('saveMany');
        return $repository;
    }


    private function mockGroup(int $groupId, int $bankAccountId, \DateTimeImmutable $lastPairing) : Group
    {
        return m::mock(Group::class, [
            'getId' => $groupId,
            'getBankAccountId' => $bankAccountId,
            'getLastPairing' => $lastPairing,
            'updateLastPairing' => null,
        ]);
    }
}
