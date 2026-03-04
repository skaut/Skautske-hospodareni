<?php

declare(strict_types=1);

namespace Model\Payment;

use Cake\Chronos\ChronosDate;
use Codeception\Test\Unit;
use DateTimeImmutable;
use Entity\BankAccount;
use Entity\Embeddable\AccountNumber;
use Helpers;
use Mockery as m;
use Model\Payment\BankAccount\IBankAccountImporter;
use Model\Payment\Fio\IFioClient;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
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

        $result = $service->findByUnit(new \Model\Common\UnitId(10));

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

    public function testGetTransactionsDelegatesToFioClient(): void
    {
        $account = $this->bankAccount(20, false);
        $repository = m::mock(IBankAccountRepository::class);
        $repository->shouldReceive('find')
            ->once()
            ->with(5)
            ->andReturn($account);

        $fio = m::mock(IFioClient::class);
        $fio->shouldReceive('getTransactions')
            ->once()
            ->withArgs(static function (ChronosDate $since, ChronosDate $until, BankAccount $passedAccount) use ($account): bool {
                return $since->equals(ChronosDate::today()->subDays(10))
                    && $until->equals(ChronosDate::today())
                    && spl_object_id($passedAccount) === spl_object_id($account);
            })
            ->andReturn(['tx']);

        $service = $this->createService(bankAccounts: $repository, fio: $fio);

        self::assertSame(['tx'], $service->getTransactions(5, 10));
    }

    private function createService(
        ?IBankAccountRepository $bankAccounts = null,
        ?IGroupRepository $groups = null,
        ?IUnitResolver $unitResolver = null,
        ?IFioClient $fio = null,
        ?IBankAccountImporter $importer = null,
        ?Cache $cache = null,
    ): BankAccountService {
        return new BankAccountService(
            $bankAccounts ?? m::mock(IBankAccountRepository::class),
            $groups ?? m::mock(IGroupRepository::class),
            $unitResolver ?? $this->unitResolver(),
            $fio ?? m::mock(IFioClient::class),
            $importer ?? m::mock(IBankAccountImporter::class),
            $cache ?? m::mock(Cache::class),
        );
    }

    /** @param array<int, int> $map */
    private function unitResolver(array $map = []): IUnitResolver
    {
        return new class($map) implements IUnitResolver
        {
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
