<?php

declare(strict_types=1);

namespace Model;

use App\Model\Bank\BankService;
use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Entity\BankTransaction as PersistedBankTransaction;
use App\Model\Bank\Enum\BankTransactionSource;
use App\Model\Bank\Exception\BankTimeout;
use App\Model\Bank\PairingCandidate;
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
use App\Model\Utils\MoneyFactory;
use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use Mockery as m;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

use function array_keys;
use function array_map;
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
        $payments = [
            new Payment($group, '-', [], MoneyFactory::fromDecimal((string) $amount), ChronosDate::now(), $vs, null, null, ''),
        ];

        Helpers::assignIdentity($payments[0], 1);

        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId])
            ->andReturn($this->candidates($payments));

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturnUsing(function (array $pairedTransactions, array $domainCandidates, array $scopeCandidates, DateTimeImmutable $time) use ($payments) {
                $transaction = $pairedTransactions[0];
                $payment = $payments[0];
                $payment->pairWithTransaction($time, \App\Model\Common\Embeddable\Transaction::fromBankTransaction($transaction));

                return ['payments' => [$payment], 'invoices' => []];
            });

        $paymentRepository = $this->mockPaymentRepository();

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
        $payments1 = [
            new Payment($group1, '-', [], MoneyFactory::fromDecimal((string) $amount), ChronosDate::now(), $vs1, null, null, ''),
            new Payment($group2, '-', [], MoneyFactory::fromDecimal((string) $amount), ChronosDate::now(), $vs2, null, null, ''),
        ];
        Helpers::assignIdentity($payments1[0], 1);

        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId1])
            ->andReturn($this->candidates([$payments1[0]]));
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId2])
            ->andReturn([]);

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturnUsing(function (array $pairedTransactions, array $domainCandidates, array $scopeCandidates, DateTimeImmutable $time) use ($payments1) {
                $transaction = $pairedTransactions[0];
                $payment = $payments1[0];
                $payment->pairWithTransaction($time, \App\Model\Common\Embeddable\Transaction::fromBankTransaction($transaction));

                return ['payments' => [$payment], 'invoices' => []];
            });

        $paymentRepository = $this->mockPaymentRepository();

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

        $payments1 = [
            new Payment($group1, '-', [], MoneyFactory::fromDecimal('200.50'), ChronosDate::now(), new VariableSymbol('123456'), null, null, ''),
        ];
        $payments2 = [
            new Payment($group2, '-', [], MoneyFactory::fromDecimal('300.50'), ChronosDate::now(), new VariableSymbol('654321'), null, null, ''),
        ];

        Helpers::assignIdentity($payments1[0], 1);
        Helpers::assignIdentity($payments2[0], 2);

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId1)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId1])
            ->andReturn($this->candidates($payments1));
        $candidates->shouldReceive('getDomainCandidatesForBankAccount')
            ->once()
            ->with($bankAccountId2)
            ->andReturn([]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')
            ->once()
            ->with([$groupId2])
            ->andReturn($this->candidates($payments2));

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturn(['payments' => [$payments1[0]], 'invoices' => []]);
        $pairingService->shouldReceive('pairAutomatically')
            ->once()
            ->andReturn(['payments' => [$payments2[0]], 'invoices' => []]);

        $paymentRepository = $this->mockPaymentRepository();

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
            new Payment($group1, '-', [], MoneyFactory::fromDecimal('100.00'), ChronosDate::now(), new VariableSymbol('123456'), null, null, ''),
            new Payment($group2, '-', [], MoneyFactory::fromDecimal('100.00'), ChronosDate::now(), new VariableSymbol('7854'), null, null, ''),
        ];
        Helpers::assignIdentity($payments1[0], 1);
        Helpers::assignIdentity($payments1[1], 2);

        $paymentRepository = m::mock(IPaymentRepository::class);

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
            ->andReturn($this->candidates([$payments1[0]]));

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
            ->andReturn($this->candidates($payments1));

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

    public function testAutomaticPairingContinuesWhenOneBankAccountFails(): void
    {
        $failingGroup = $this->group(12, 159, null);
        $pairedGroup = $this->group(34, 357, null);
        $failingPayment = new Payment($failingGroup, '-', [], MoneyFactory::fromDecimal('100'), ChronosDate::now(), new VariableSymbol('123'), null, null, '');
        $pairedPayment = new Payment($pairedGroup, '-', [], MoneyFactory::fromDecimal('100'), ChronosDate::now(), new VariableSymbol('7854'), null, null, '');

        $groups = m::mock(IGroupRepository::class);
        $groups->shouldReceive('findAutomaticPairingEnabled')->once()->andReturn([$failingGroup, $pairedGroup]);
        $groups->shouldReceive('findByIds')->once()->with([12])->andReturn([12 => $failingGroup]);
        $groups->shouldReceive('findByIds')->once()->with([34])->andReturn([34 => $pairedGroup]);
        $groups->shouldReceive('save')->once()->with($pairedGroup);

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->withArgs(static fn (BankAccount $account): bool => $account->getId() === 159)
            ->andThrow(new BankTimeout('There was an error when connecting to FIO'));
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->withArgs(static fn (BankAccount $account): bool => $account->getId() === 357)
            ->andReturn([]);

        $candidates = m::mock(BankPairingCandidateProvider::class, ['getDomainCandidatesForBankAccount' => []]);
        $candidates->allows('getScopedCandidatesForGroups')->with([12])->andReturn($this->candidates([$failingPayment]));
        $candidates->allows('getScopedCandidatesForGroups')->with([34])->andReturn($this->candidates([$pairedPayment]));

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')->once()->andReturn(['payments' => [$pairedPayment], 'invoices' => []]);

        $payments = m::mock(IPaymentRepository::class);
        $payments->shouldReceive('findOpenByGroups')->once()->with([12, 34])->andReturn([$failingPayment, $pairedPayment]);
        $payments->shouldReceive('saveMany')->once()->with([$pairedPayment]);

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Automatic pairing of bank account #159 failed: There was an error when connecting to FIO', m::type('array'));
        $logger->shouldReceive('info')->once()->with('Automatic pairing paired 1 payments on 2 bank accounts');

        $bankService = $this->createBankService(
            $groups,
            $bank,
            $payments,
            $this->mockBankAccountRepository([
                159 => $this->mockBankAccount(159, '123'),
                357 => $this->mockBankAccount(357, '852'),
            ]),
            $candidates,
            $pairingService,
            $logger,
        );

        $results = $bankService->pairAutomaticGroups();

        $this->assertCount(1, $results);
        $this->assertSame(1, $results[0]->getCount());
    }

    public function testAutomaticPairingLeavesOutGpcAccountsAndGroupsWithoutUnpaidPayments(): void
    {
        $fioGroup = $this->group(12, 159, null);
        $gpcGroup = $this->group(34, 357, null);
        $paidGroup = $this->group(56, 951, null);
        $fioPayment = new Payment($fioGroup, '-', [], MoneyFactory::fromDecimal('100'), ChronosDate::now(), new VariableSymbol('123'), null, null, '');
        $gpcPayment = new Payment($gpcGroup, '-', [], MoneyFactory::fromDecimal('100'), ChronosDate::now(), new VariableSymbol('456'), null, null, '');

        $groups = m::mock(IGroupRepository::class);
        $groups->shouldReceive('findAutomaticPairingEnabled')->once()->andReturn([$fioGroup, $gpcGroup, $paidGroup]);
        $groups->shouldReceive('findByIds')->once()->with([12])->andReturn([12 => $fioGroup]);
        $groups->shouldReceive('save')->once()->with($fioGroup);

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->withArgs(static fn (BankAccount $account): bool => $account->getId() === 159)
            ->andReturn([]);

        $candidates = m::mock(BankPairingCandidateProvider::class, ['getDomainCandidatesForBankAccount' => []]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')->once()->with([12])->andReturn($this->candidates([$fioPayment]));

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')->once()->andReturn(['payments' => [], 'invoices' => []]);

        $payments = m::mock(IPaymentRepository::class);
        $payments->shouldReceive('findOpenByGroups')->once()->with([12, 34, 56])->andReturn([$fioPayment, $gpcPayment]);
        $payments->shouldReceive('saveMany')->once();

        $logger = m::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->once()->with('Automatic pairing paired 0 payments on 1 bank accounts');

        $bankService = $this->createBankService(
            $groups,
            $bank,
            $payments,
            $this->mockBankAccountRepository([
                159 => $this->mockBankAccount(159, '123'),
                357 => $this->mockBankAccount(357, null, BankTransactionSource::GPC),
            ]),
            $candidates,
            $pairingService,
            $logger,
        );

        $this->assertCount(1, $bankService->pairAutomaticGroups());
    }

    public function testBankIsNotAskedWhenNoPaymentIsLeftToPair(): void
    {
        $group = $this->group(12, 159, null);

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldNotReceive('getPersistentTransactionsForPeriod');

        $candidates = m::mock(BankPairingCandidateProvider::class);
        $candidates->shouldReceive('getScopedCandidatesForGroups')->once()->with([12])->andReturn([]);

        $bankService = $this->createBankService(
            $this->mockGroupRepository([12 => $group]),
            $bank,
            $this->mockPaymentRepository(),
            $this->mockBankAccountRepository([159 => $this->mockBankAccount(159, '123')]),
            $candidates,
            m::mock(BankTransactionPairingService::class),
        );

        $this->assertSame([], $bankService->pairAllGroups([12]));
    }

    /** A run stores today as the last pairing, so the next run of the same day loads the whole day again. */
    public function testNextRunOnTheSameDayLoadsTheWholeDayAgain(): void
    {
        $group = $this->group(12, 159, ChronosDate::today()->toNative());
        $payment = new Payment($group, '-', [], MoneyFactory::fromDecimal('100'), ChronosDate::now(), new VariableSymbol('123'), null, null, '');

        $bank = m::mock(BankTransactionService::class);
        $bank->shouldReceive('getPersistentTransactionsForPeriod')
            ->once()
            ->withArgs(static fn (BankAccount $account, ChronosDate $since, ChronosDate $until): bool => $since->equals(ChronosDate::today())
                && $until->equals(ChronosDate::today()))
            ->andReturn([]);

        $candidates = m::mock(BankPairingCandidateProvider::class, ['getDomainCandidatesForBankAccount' => []]);
        $candidates->shouldReceive('getScopedCandidatesForGroups')->once()->with([12])->andReturn($this->candidates([$payment]));

        $pairingService = m::mock(BankTransactionPairingService::class);
        $pairingService->shouldReceive('pairAutomatically')->once()->andReturn(['payments' => [], 'invoices' => []]);

        $bankService = $this->createBankService(
            $this->mockGroupRepository([12 => $group]),
            $bank,
            $this->mockPaymentRepository(),
            $this->mockBankAccountRepository([159 => $this->mockBankAccount(159, '123')]),
            $candidates,
            $pairingService,
        );

        $bankService->pairAllGroups([12]);
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

    private function mockBankAccount(int $id, ?string $token, BankTransactionSource $source = BankTransactionSource::FIO): BankAccount
    {
        return m::mock(BankAccount::class, [
            'getId' => $id,
            'getToken' => $token,
            'getName' => 'ucet jednotky',
            'getTransactionSource' => $source,
        ]);
    }

    private function mockPaymentRepository(): IPaymentRepository
    {
        $repository = m::mock(IPaymentRepository::class);
        $repository->shouldReceive('saveMany');

        return $repository;
    }

    /**
     * @param list<Payment> $payments
     *
     * @return list<PairingCandidate>
     */
    private function candidates(array $payments): array
    {
        return array_map(static fn (Payment $payment): PairingCandidate => PairingCandidate::forPayment($payment), $payments);
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
                MoneyFactory::fromDecimal((string) $amount),
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
        ?LoggerInterface $logger = null,
    ): BankService {
        return new BankService(
            $groups,
            $bank,
            $paymentRepository,
            $bankAccounts,
            $candidates,
            new BankAccountPairingRunner($candidates, $pairingService),
            $logger ?? new NullLogger(),
        );
    }
}
