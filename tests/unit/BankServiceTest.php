<?php

declare(strict_types=1);

namespace Model;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use Mockery as m;
use Model\Bank\Fio\Transaction;
use Model\DTO\Payment\PairingResult;
use Model\Payment\BankAccount;
use Model\Payment\EmailTemplate;
use Model\Payment\EmailType;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Group;
use Model\Payment\Payment;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Repositories\IPaymentRepository;
use Model\Payment\VariableSymbol;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

use function array_keys;
use function array_map;
use function array_merge;
use function count;

final class BankServiceTest extends Unit
{
    /** @see https://github.com/skaut/Skautske-hospodareni/pull/508 */
    public function testPaymentIsPairedOnlyOnceForDuplicateTransactions(): void
    {
        $groupId       = 123;
        $bankAccountId = 456;

        $group  = $this->group($groupId, $bankAccountId, new DateTimeImmutable('- 5 days'));
        $groups = $this->mockGroupRepository([$groupId => $group]);

        $bankAccount = $this->mockBankAccount($bankAccountId, '123');

        $bankAccounts = $this->mockBankAccountRepository([$bankAccountId => $bankAccount]);

        $amount  = 200.50;
        $vs      = new VariableSymbol('123456');
        $account = (string) Helpers::createAccountNumber();

        $transactions = array_map(
            static function (string $id) use ($amount, $vs, $account) {
                $today = new DateTimeImmutable();

                return new Transaction($id, $today, $amount, $account, 'František Maša', $vs->toInt(), null, 'note' . $id);
            },
            ['123', '456'],
        );

        $bank = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions);

        $payments = [
            new Payment($group, '-', [], $amount, ChronosDate::now(), $vs, null, null, ''),
        ];

        Helpers::assignIdentity($payments[0], 1);

        $paymentRepository = $this->mockPaymentRepository([$groupId => $payments]);

        $bankService = new BankService($groups, $bank, $paymentRepository, $bankAccounts);

        $bankService->pairAllGroups([$groupId]);

        $transaction = $payments[0]->getTransaction();
        $this->assertSame('123', $transaction->getId());
        $this->assertSame($account, $transaction->getBankAccount());
        $this->assertSame('note123', $transaction->getNote());
        $this->assertSame('František Maša', $transaction->getPayer());
    }

    public function testPaymentIsPairedOnlyByJoinedAccount(): void
    {
        $groupId1       = 12;
        $bankAccountId1 = 159;
        $lastPairing    = new DateTimeImmutable('- 5 days');

        $group1 = $this->group($groupId1, $bankAccountId1, $lastPairing);

        $groupId2       = 34;
        $bankAccountId2 = 357;

        $group2 = $this->group($groupId2, $bankAccountId2, $lastPairing);

        $groups = $this->mockGroupRepository([$groupId1 => $group1, $groupId2 => $group2]);

        $bankAccount1 = $this->mockBankAccount($bankAccountId1, '123');
        $bankAccount2 = $this->mockBankAccount($bankAccountId2, '852');

        $bankAccounts = $this->mockBankAccountRepository([
            $bankAccountId1 => $bankAccount1,
            $bankAccountId2 => $bankAccount2,
        ]);

        $today   = new DateTimeImmutable();
        $amount  = 200.50;
        $vs1     = new VariableSymbol('123456');
        $account = (string) Helpers::createAccountNumber();
        $vs2     = new VariableSymbol('7854');

        $transactions1 = [
            new Transaction('123', $today, $amount, $account, 'František Maša', $vs1->toInt(), null, null),
        ];

        $bank = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions1);

        $payments1 = [
            new Payment($group1, '-', [], $amount, ChronosDate::now(), $vs1, null, null, ''),
            new Payment($group2, '-', [], $amount, ChronosDate::now(), $vs2, null, null, ''),
        ];
        Helpers::assignIdentity($payments1[0], 1);

        $payments2 = [];

        $paymentRepository = $this->mockPaymentRepository([
            $groupId1 => $payments1,
            $groupId2 => $payments2,
        ]);

        $bankService = new BankService($groups, $bank, $paymentRepository, $bankAccounts);

        /** @var PairingResult[] $pairingResults */
        $pairingResults = $bankService->pairAllGroups([$groupId1, $groupId2]);
        $this->assertSame(1, count($pairingResults));
        $this->assertSame(1, $pairingResults[0]->getCount());
    }

    /** @see https://github.com/skaut/Skautske-hospodareni/issues/1608 */
    public function testDefaultIntervalIsUsedForGroupThatIsNotPairedYetWhenPairingMultipleGroups(): void
    {
        $group1 = $this->group(1, 12, new DateTimeImmutable('- 5 days'));
        $group2 = $this->group(2, 12, null);

        $bankAccounts = $this->mockBankAccountRepository([12 => $this->mockBankAccount(12, '123')]);

        $transactions1 = [
            new Transaction(
                '123',
                new DateTimeImmutable(),
                200.50,
                (string) Helpers::createAccountNumber(),
                'František Maša',
                123456,
                null,
                null,
            ),
        ];

        $payments1 = [
            new Payment($group1, '-', [], 100, ChronosDate::now(), new VariableSymbol('123456'), null, null, ''),
            new Payment($group2, '-', [], 100, ChronosDate::now(), new VariableSymbol('7854'), null, null, ''),
        ];

        $payments2 = [];

        $paymentRepository = m::mock(IPaymentRepository::class);
        $paymentRepository->shouldReceive('findByMultipleGroups')
            ->once()
            ->with([$group1->getId()])
            ->andReturn($payments1);
        $paymentRepository->shouldReceive('findByMultipleGroups')
            ->once()
            ->with([$group1->getId(), $group2->getId()])
            ->andReturn(array_merge($payments1, $payments2));

        // Pair group #1
        $bank = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->andReturn($transactions1);

        $paymentRepository->shouldReceive('saveMany');

        $bankService = new BankService(
            $this->mockGroupRepository([$group1->getId() => $group1]),
            $bank,
            $paymentRepository,
            $bankAccounts,
        );
        $bankService->pairAllGroups([$group1->getId()]);

        // Pair group 1# and #2
        $bank = m::mock(IFioClient::class);
        $bank->shouldReceive('getTransactions')
            ->once()
            ->withArgs(
                fn (ChronosDate $since, ChronosDate $until) => $since->equals(ChronosDate::today()->subDays(60))
                    && $until->equals(ChronosDate::today())
            )
            ->andReturn($transactions1);

        $paymentRepository->shouldReceive('saveMany');

        $bankService = new BankService(
            $this->mockGroupRepository([
                $group1->getId() => $group1,
                $group2->getId() => $group2,
            ]),
            $bank,
            $paymentRepository,
            $bankAccounts,
        );
        $bankService->pairAllGroups([$group1->getId(), $group2->getId()]);
    }

    /** @param BankAccount[] $bankAccounts */
    public function mockBankAccountRepository(array $bankAccounts): IBankAccountRepository
    {
        $repository = m::mock(IBankAccountRepository::class);
        foreach ($bankAccounts as $bankAccountId => $bankAccount) {
            $repository->shouldReceive('find')
                ->with($bankAccountId)
                ->andReturn($bankAccount);
        }

        return $repository;
    }

    /** @param Group[] $groups */
    private function mockGroupRepository(array $groups): IGroupRepository
    {
        $repository = m::mock(IGroupRepository::class);
        $repository->shouldReceive('findByIds')
            ->once()
            ->with(array_keys($groups))
            ->andReturn($groups);
        $repository->shouldReceive('save');

        return $repository;
    }

    private function mockBankAccount(int $id, string $token): BankAccount
    {
        return m::mock(BankAccount::class, [
            'getId' => $id,
            'getToken' => $token,
            'getName' => 'ucet jednotky',
        ]);
    }

    /** @param array<int, list<Payment>> $paymentsByGroup */
    private function mockPaymentRepository(array $paymentsByGroup): IPaymentRepository
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

    private function group(int $groupId, int $bankAccountId, DateTimeImmutable|null $lastPairing): Group
    {
        $group = new Group(
            [1],
            null,
            'Foo',
            new Group\PaymentDefaults(null, null, null, null),
            new DateTimeImmutable(),
            [EmailType::PAYMENT_INFO => new EmailTemplate('', '')],
            null,
            $this->mockBankAccount($bankAccountId, '123'),
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );

        if ($lastPairing !== null) {
            $group->updateLastPairing($lastPairing);
        }

        Helpers::assignIdentity($group, $groupId);

        return $group;
    }
}
