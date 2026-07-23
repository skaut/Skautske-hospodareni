<?php

declare(strict_types=1);

namespace Model;

use App\Model\Bank\BankService;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction as PersistedBankTransaction;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Services\BankAccountPairingRunner;
use App\Model\Bank\Services\BankPairingCandidateProvider;
use App\Model\Bank\Services\BankTransactionPairingService;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\Bank\Transaction;
use App\Model\DTO\Payment\PairingResult;
use App\Model\Payment\EmailTemplate;
use App\Model\Payment\EmailType;
use App\Model\Payment\Group;
use App\Model\Payment\Payment;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Repositories\IPaymentRepository;
use App\Model\Payment\VariableSymbol;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use Mockery as m;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

use function array_keys;
use function array_merge;
use function count;

final class BankServiceTest extends Unit
{
    /** @see https://github.com/skaut/Skautske-hospodareni/pull/508 */
    public function testPaymentIsPairedOnlyOnceForDuplicateTransactions(): void
    {
        $groupId = 123;
        $bankAccountId = 456;

        $group = $this->group($groupId, $bankAccountId, new DateTimeImmutable('- 5 days'));
        $groups = $this->mockGroupRepository([$groupId => $group]);

        $bankAccount = $this->mockBankAccount($bankAccountId, '123');

        $bankAccounts = $this->mockBankAccountRepository([$bankAccountId => $bankAccount]);

        $amount = 200.50;
        $vs = new VariableSymbol('123456');
        $account = (string) Helpers::createAccountNumber();

        $transactions = [
            $this->persistentTransaction('123', $amount, $vs, $account, 'note123'),
            $this->persistentTransaction('456', $amount, $vs, $account, 'note456'),
        ];

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->andReturn($transactions);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId])
            ->andReturn([]);

        $payments = [
            new Payment($group, '-', [], $amount, ChronosDate::now(), $vs, null, null, ''),
        ];

        Helpers::assignIdentity($payments[0], 1);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturnUsing(function (array $pairedTransactions, array $domainCandidates, array $scopeCandidates, DateTimeImmutable $time) use ($payments) {
                $transaction = $pairedTransactions[0];
                $payment = $payments[0];
                $payment->pairWithTransaction($time, \App\Model\Common\Embeddable\Transaction::fromBankTransaction($transaction));

                return ['payments' => [$payment], 'invoices' => []];
            });

        $paymentRepository = $this->mockPaymentRepository([$groupId => $payments]);

        $bankService = $this->createBankService($groups, $bank, $paymentRepository, $bankAccounts, $candidates, $pairingService);

        $bankService->pairAllGroups([$groupId]);

        $transaction = $payments[0]->getTransaction();
        $this->assertNotNull($transaction);
        $this->assertSame('123', $transaction->getId());
        $this->assertSame($account, $transaction->getBankAccount());
        $this->assertSame('note123', $transaction->getNote());
        $this->assertSame('František Maša', $transaction->getPayer());
    }

    public function testPaymentIsPairedOnlyByJoinedAccount(): void
    {
        $groupId1 = 12;
        $bankAccountId1 = 159;
        $lastPairing = new DateTimeImmutable('- 5 days');

        $group1 = $this->group($groupId1, $bankAccountId1, $lastPairing);

        $groupId2 = 34;
        $bankAccountId2 = 357;

        $group2 = $this->group($groupId2, $bankAccountId2, $lastPairing);

        $groups = $this->mockGroupRepository([$groupId1 => $group1, $groupId2 => $group2]);

        $bankAccount1 = $this->mockBankAccount($bankAccountId1, '123');
        $bankAccount2 = $this->mockBankAccount($bankAccountId2, '852');

        $bankAccounts = $this->mockBankAccountRepository([
            $bankAccountId1 => $bankAccount1,
            $bankAccountId2 => $bankAccount2,
        ]);

        $today = new DateTimeImmutable();
        $amount = 200.50;
        $vs1 = new VariableSymbol('123456');
        $account = (string) Helpers::createAccountNumber();
        $vs2 = new VariableSymbol('7854');

        $transactions1 = [
            $this->persistentTransaction('123', $amount, $vs1, $account),
        ];

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->andReturn($transactions1);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId1)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId1])
            ->andReturn([]);

        $payments1 = [
            new Payment($group1, '-', [], $amount, ChronosDate::now(), $vs1, null, null, ''),
            new Payment($group2, '-', [], $amount, ChronosDate::now(), $vs2, null, null, ''),
        ];
        Helpers::assignIdentity($payments1[0], 1);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturnUsing(function (array $pairedTransactions, array $domainCandidates, array $scopeCandidates, DateTimeImmutable $time) use ($payments1) {
                $transaction = $pairedTransactions[0];
                $payment = $payments1[0];
                $payment->pairWithTransaction($time, \App\Model\Common\Embeddable\Transaction::fromBankTransaction($transaction));

                return ['payments' => [$payment], 'invoices' => []];
            });

        $payments2 = [];

        $paymentRepository = $this->mockPaymentRepository([
            $groupId1 => $payments1,
            $groupId2 => $payments2,
        ]);

        $bankService = $this->createBankService($groups, $bank, $paymentRepository, $bankAccounts, $candidates, $pairingService);

        /** @var PairingResult[] $pairingResults */
        $pairingResults = $bankService->pairAllGroups([$groupId1, $groupId2]);
        $this->assertSame(1, count($pairingResults));
        $this->assertSame(1, $pairingResults[0]->getCount());
    }

    public function testPairingResultCountIsReportedPerBankAccount(): void
    {
        $groupId1 = 12;
        $bankAccountId1 = 159;
        $groupId2 = 34;
        $bankAccountId2 = 357;
        $lastPairing = new DateTimeImmutable('- 5 days');

        $group1 = $this->group($groupId1, $bankAccountId1, $lastPairing);
        $group2 = $this->group($groupId2, $bankAccountId2, $lastPairing);
        $groups = $this->mockGroupRepository([$groupId1 => $group1, $groupId2 => $group2]);

        $bankAccount1 = $this->mockBankAccount($bankAccountId1, '123');
        $bankAccount2 = $this->mockBankAccount($bankAccountId2, '852');
        $bankAccounts = $this->mockBankAccountRepository([
            $bankAccountId1 => $bankAccount1,
            $bankAccountId2 => $bankAccount2,
        ]);

        $transaction1 = $this->persistentTransaction('123', 200.50, new VariableSymbol('123456'), (string) Helpers::createAccountNumber());
        $transaction2 = $this->persistentTransaction('456', 300.50, new VariableSymbol('654321'), (string) Helpers::createAccountNumber());

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->andReturn([$transaction1]);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->andReturn([$transaction2]);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId1)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId1])
            ->andReturn([]);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId2)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId2])
            ->andReturn([]);

        $payments1 = [
            new Payment($group1, '-', [], 200.50, ChronosDate::now(), new VariableSymbol('123456'), null, null, ''),
        ];
        $payments2 = [
            new Payment($group2, '-', [], 300.50, ChronosDate::now(), new VariableSymbol('654321'), null, null, ''),
        ];

        Helpers::assignIdentity($payments1[0], 1);
        Helpers::assignIdentity($payments2[0], 2);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturn(['payments' => [$payments1[0]], 'invoices' => []]);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturn(['payments' => [$payments2[0]], 'invoices' => []]);

        $paymentRepository = $this->mockPaymentRepository([
            $groupId1 => $payments1,
            $groupId2 => $payments2,
        ]);

        $bankService = $this->createBankService($groups, $bank, $paymentRepository, $bankAccounts, $candidates, $pairingService);

        $pairingResults = $bankService->pairAllGroups([$groupId1, $groupId2]);

        $this->assertCount(2, $pairingResults);
        $this->assertSame(1, $pairingResults[0]->getCount());
        $this->assertSame(1, $pairingResults[1]->getCount());
    }

    /** @see https://github.com/skaut/Skautske-hospodareni/issues/1608 */
    public function testDefaultIntervalIsUsedForGroupThatIsNotPairedYetWhenPairingMultipleGroups(): void
    {
        $group1 = $this->group(1, 12, new DateTimeImmutable('- 5 days'));
        $group2 = $this->group(2, 12, null);

        $bankAccounts = $this->mockBankAccountRepository([12 => $this->mockBankAccount(12, '123')]);

        $transactions1 = [
            $this->persistentTransaction(
                '123',
                200.50,
                new VariableSymbol('123456'),
                (string) Helpers::createAccountNumber(),
                null,
            ),
        ];

        $payments1 = [
            new Payment($group1, '-', [], 100, ChronosDate::now(), new VariableSymbol('123456'), null, null, ''),
            new Payment($group2, '-', [], 100, ChronosDate::now(), new VariableSymbol('7854'), null, null, ''),
        ];
        Helpers::assignIdentity($payments1[0], 1);
        Helpers::assignIdentity($payments1[1], 2);

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
        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->andReturn($transactions1);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with(12)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$group1->getId()])
            ->andReturn([]);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturn(['payments' => [], 'invoices' => []]);

        $paymentRepository->shouldReceive('saveMany');

        $bankService = $this->createBankService(
            $this->mockGroupRepository([$group1->getId() => $group1]),
            $bank,
            $paymentRepository,
            $bankAccounts,
            $candidates,
            $pairingService,
        );
        $bankService->pairAllGroups([(int) $group1->getId()]);

        // Pair group 1# and #2
        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->withArgs(
                fn (BankAccount $account, ChronosDate $since, ChronosDate $until) => $since->equals(ChronosDate::today()->subDays(60))
                    && $until->equals(ChronosDate::today()),
            )
            ->andReturn($transactions1);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with(12)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$group1->getId(), $group2->getId()])
            ->andReturn([]);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturnUsing(function (array $pairedTransactions, array $domainCandidates, array $scopeCandidates, DateTimeImmutable $time) use (&$payments1) {
                $transaction = $pairedTransactions[0];
                $payment = $payments1[0];
                $payment->pairWithTransaction($time, \App\Model\Common\Embeddable\Transaction::fromBankTransaction($transaction));

                return ['payments' => [$payment], 'invoices' => []];
            });

        $paymentRepository->shouldReceive('saveMany');

        $bankService = $this->createBankService(
            $this->mockGroupRepository([
                $group1->getId() => $group1,
                $group2->getId() => $group2,
            ]),
            $bank,
            $paymentRepository,
            $bankAccounts,
            $candidates,
            $pairingService,
        );
        $bankService->pairAllGroups([(int) $group1->getId(), (int) $group2->getId()]);
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
            'getTransactionSource' => BankTransactionSource::FIO,
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

    private function group(int $groupId, int $bankAccountId, ?DateTimeImmutable $lastPairing): Group
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

    private function persistentTransaction(
        string $id,
        float $amount,
        VariableSymbol $variableSymbol,
        string $counterAccount,
        ?string $note = null,
    ): PersistedBankTransaction {
        $today = new DateTimeImmutable();

        return new PersistedBankTransaction(
            $this->mockBankAccount(999, 'token'),
            new Transaction(
                $id,
                BankTransactionSource::FIO,
                $today,
                $amount,
                $counterAccount,
                'František Maša',
                $variableSymbol->toInt(),
                null,
                $note,
                $id,
            ),
            $today,
        );
    }

    private function createBankService(
        IGroupRepository $groups,
        BankTransactionService $bank,
        IPaymentRepository $paymentRepository,
        IBankAccountRepository $bankAccounts,
        BankPairingCandidateProvider $candidates,
        BankTransactionPairingService $pairingService,
    ): BankService {
        return new BankService(
            $groups,
            $bank,
            $paymentRepository,
            $bankAccounts,
            $candidates,
            new BankAccountPairingRunner($candidates, $pairingService),
        );
    }
}
