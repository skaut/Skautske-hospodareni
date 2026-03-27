<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers\BankAccount;

use App\Model\Bank\Entity\BankAccount;
use App\Model\Infrastructure\Repositories\Payment\BankAccountRepository;
use App\Model\Payment\IUnitResolver;
use App\Model\Payment\ReadModel\Queries\BankAccount\BankAccountsAccessibleByUnitsQuery;
use App\Model\Payment\Services\BankAccountAccessChecker;
use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Mockery as m;

final class BankAccountsAccessibleByUnitsQueryHandlerTest extends IntegrationTest
{
    /** @return string[] */
    protected function getTestedAggregateRoots(): array
    {
        return [BankAccount::class];
    }

    public function testReturnsOnlyAccountsAccessibleToAllRequestedUnits(): void
    {
        $allowedAccount = $this->createBankAccount(20, 'Povolený účet', true);
        $notSharedAccount = $this->createBankAccount(20, 'Jen pro středisko', false);
        $foreignAccount = $this->createBankAccount(50, 'Cizí účet', true);

        $unitResolver = m::mock(IUnitResolver::class);
        $unitResolver->shouldReceive('getOfficialUnitId')->with(10)->andReturn(20);
        $unitResolver->shouldReceive('getOfficialUnitId')->with(11)->andReturn(20);
        $unitResolver->shouldReceive('getOfficialUnitId')->with(12)->andReturn(50);

        $handler = new BankAccountsAccessibleByUnitsQueryHandler(
            new BankAccountAccessChecker(new BankAccountRepository($this->entityManager), $unitResolver),
            new BankAccountRepository($this->entityManager),
            $unitResolver,
        );

        $accessibleForSubunits = $handler(new BankAccountsAccessibleByUnitsQuery([10, 11]));
        self::assertSame([$allowedAccount->getId()], array_map(
            static fn (\App\Model\DTO\Payment\BankAccount $account): int => $account->getId(),
            $accessibleForSubunits,
        ));

        $accessibleForMixedTree = $handler(new BankAccountsAccessibleByUnitsQuery([10, 12]));
        self::assertSame([], $accessibleForMixedTree);

        self::assertNotSame($foreignAccount->getId(), $allowedAccount->getId());
        self::assertNotSame($notSharedAccount->getId(), $allowedAccount->getId());
    }

    private function createBankAccount(int $unitId, string $name, bool $allowedForSubunits): BankAccount
    {
        $account = new BankAccount(
            $unitId,
            $name,
            Helpers::createAccountNumber(),
            null,
            new DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => $unitId]),
        );

        if ($allowedForSubunits) {
            $account->allowForSubunits();
        }

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }
}
