<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Bank\Repository\BankTransactionImportBatchRepository;
use App\Model\Bank\Services\BankTransactionService;
use App\Model\Common\Embeddable\AccountNumber;
use App\Model\Invoice\Repository\InvoiceRepository;
use App\Model\Invoice\Repository\InvoiceSequenceRepository;
use App\Model\Payment\BankAccount\IBankAccountImporter;
use App\Model\Payment\Repositories\IBankAccountRepository;
use App\Model\Payment\Repositories\IGroupRepository;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Helpers;
use Mockery as m;
use Nette\Caching\Cache;

final class BankAccountServiceTest extends Unit
{
    public function testFindReturnsNullWhenAccountDoesNotExist(): void
    {
        $repository = m::mock(IBankAccountRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(123)
            ->andThrow(new BankAccountNotFound());

        $service = $this->createService(bankAccounts: $repository);

        self::assertNull($service->find(123));
    }

    public function testFindByUnitReturnsOwnedAndAllowedAccountsOnly(): void
    {
        $owned = $this->bankAccount(10, false);
        $shared = $this->bankAccount(20, true);
        $foreign = $this->bankAccount(20, false);

        $repository = m::mock(IBankAccountRepository::class);
        $repository->shouldReceive('findByUnit')
            ->once()
            ->with(20)
            ->andReturn([$owned, $shared, $foreign]);

        $service = $this->createService(
            bankAccounts: $repository,
            unitResolver: $this->unitResolver([10 => 20]),
        );

        $result = $service->findByUnit(new \App\Model\Common\UnitId(10));

        self::assertCount(2, $result);
        self::assertSame([$owned->getName(), $shared->getName()], array_map(static fn ($account) => $account->getName(), $result));
    }

    public function testImportFromSkautisSkipsExistingAccounts(): void
    {
        $existing = Helpers::createAccountNumber();
        $new = new AccountNumber(null, '2000942152', '2010');

        $repository = m::mock(IBankAccountRepository::class);
        $repository->shouldReceive('findByUnit')
            ->once()
            ->with(20)
            ->andReturn([
                new BankAccount(20, 'existing', $existing, null, new DateTimeImmutable(), $this->unitResolver()),
            ]);
        $repository->shouldReceive('save')
            ->once()
            ->withArgs(static fn (BankAccount $account): bool => (string) $account->getNumber() === (string) $new);

        $importer = m::mock(IBankAccountImporter::class);
        $importer->shouldReceive('import')
            ->once()
            ->with(20)
            ->andReturn([$existing, $new]);

        $service = $this->createService(
            bankAccounts: $repository,
            unitResolver: $this->unitResolver([10 => 20, 20 => 20]),
            importer: $importer,
        );

        $service->importFromSkautis(10);
    }

    public function testImportFromSkautisThrowsWhenNoNewAccountsExist(): void
    {
        $existing = Helpers::createAccountNumber();

        $repository = m::mock(IBankAccountRepository::class);
        $repository->shouldReceive('findByUnit')
            ->once()
            ->with(20)
            ->andReturn([
                new BankAccount(20, 'existing', $existing, null, new DateTimeImmutable(), $this->unitResolver()),
            ]);

        $importer = m::mock(IBankAccountImporter::class);
        $importer->shouldReceive('import')
            ->once()
            ->with(20)
            ->andReturn([$existing]);

        $service = $this->createService(
            bankAccounts: $repository,
            unitResolver: $this->unitResolver([10 => 20, 20 => 20]),
            importer: $importer,
        );

        $this->expectException(BankAccountNotFound::class);

        $service->importFromSkautis(10);
    }

    public function testGetTransactionsDelegatesToBankTransactionService(): void
    {
        $transactions = m::mock(BankTransactionService::class);
        $transactions->shouldReceive('getTransactions')
            ->once()
            ->with(5, 10)
            ->andReturn(['tx']);

        $service = $this->createService(transactions: $transactions);

        self::assertSame(['tx'], $service->getTransactions(5, 10));
    }

    private function createService(
        ?IBankAccountRepository $bankAccounts = null,
        ?IGroupRepository $groups = null,
        ?IUnitResolver $unitResolver = null,
        ?BankTransactionService $transactions = null,
        ?IBankAccountImporter $importer = null,
        ?Cache $cache = null,
        ?InvoiceSequenceRepository $invoiceSequences = null,
        ?BankTransactionImportBatchRepository $transactionImportBatches = null,
    ): BankAccountService {
        return new BankAccountService(
            $bankAccounts ?? m::mock(IBankAccountRepository::class),
            $groups ?? m::mock(IGroupRepository::class),
            $unitResolver ?? $this->unitResolver(),
            $transactions ?? m::mock(BankTransactionService::class),
            $importer ?? m::mock(IBankAccountImporter::class),
            $cache ?? m::mock(Cache::class),
            m::mock(Repositories\IPaymentRepository::class),
            m::mock(InvoiceRepository::class),
            $invoiceSequences ?? m::mock(InvoiceSequenceRepository::class),
            $transactionImportBatches ?? m::mock(BankTransactionImportBatchRepository::class),
        );
    }

    /** @param array<int, int> $map */
    private function unitResolver(array $map = []): IUnitResolver
    {
        return new class($map) implements IUnitResolver {
            /** @param array<int, int> $map */
            public function __construct(private array $map)
            {
            }

            public function getOfficialUnitId(int $unitId): int
            {
                return $this->map[$unitId] ?? $unitId;
            }
        };
    }

    private function bankAccount(int $unitId, bool $allowedForSubunits): BankAccount
    {
        $account = new BankAccount(
            $unitId,
            'Account '.$unitId.($allowedForSubunits ? '-shared' : '-owned'),
            Helpers::createAccountNumber(),
            null,
            new DateTimeImmutable(),
            $this->unitResolver(),
        );

        if ($allowedForSubunits) {
            $account->allowForSubunits();
        }

        Helpers::assignIdentity($account, $unitId + ($allowedForSubunits ? 1000 : 0));

        return $account;
    }
}
