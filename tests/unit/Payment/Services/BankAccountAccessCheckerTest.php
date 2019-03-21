<?php

declare(strict_types=1);

namespace Model\Payment\Services;

use Codeception\Test\Unit;
use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFound;
use Model\Payment\IUnitResolver;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\UnitResolverStub;

final class BankAccountAccessCheckerTest extends Unit
{
    /** @var UnitResolverStub */
    private $unitResolver;

    protected function setUp() : void
    {
        parent::setUp();
        $this->unitResolver = new UnitResolverStub();
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            11 => 20,
            12 => 50,
            20 => 20,
            50 => 50,
        ]);
    }

    /**
     * @return mixed[][]
     */
    public static function dataValidUnitsToKeepForBankAccount() : array
    {
        return [
            [[20], false, 'official unit for account not allowed for subunits'],
            [[10, 11], true, 'subunits for account allowed for subunits'],
            [[20, 10], true, 'official unit and subunit for account allowed for subunits'],
        ];
    }

    /**
     * @dataProvider dataValidUnitsToKeepForBankAccount
     * @param int[] $unitIds
     */
    public function testValidUnitsToKeepForBankAccount(array $unitIds, bool $allowedForSubunits, string $message) : void
    {
        $bankAccount = $this->createBankAccount(20, $allowedForSubunits);

        $bankAccountRepository = \Mockery::mock(IBankAccountRepository::class);
        $bankAccountRepository
            ->shouldReceive('find')
            ->once()
            ->withArgs([1])
            ->andReturn($bankAccount);

        $checker = new BankAccountAccessChecker($bankAccountRepository, $this->unitResolver);

        $this->assertTrue(
            $checker->allUnitsHaveAccessToBankAccount($unitIds, 1),
            $message . ' should have access to bank account'
        );
    }

    /**
     * @return mixed[][]
     */
    public static function dataUnitsWithNoAccessToBankAccount() : array
    {
        return [
            [[20, 12], true, 'units with different official unit'],
            [[20, 20], false, 'subunits for account not allowed for subunits'],
        ];
    }

    /**
     * @dataProvider dataUnitsWithNoAccessToBankAccount
     * @param int[] $unitIds
     */
    public function testReturnsFalseWhenAtLeastOneUnitHasNoAccessToBankAccount(
        array $unitIds,
        bool $allowedForSubunits,
        string $message
    ) : void {
        $bankAccount = $this->createBankAccount(20, $allowedForSubunits);

        $bankAccountRepository = \Mockery::mock(IBankAccountRepository::class);
        $bankAccountRepository
            ->shouldReceive('find')
            ->once()
            ->withArgs([1])
            ->andReturn($bankAccount);

        $checker = new BankAccountAccessChecker($bankAccountRepository, $this->unitResolver);

        $this->assertFalse(
            $checker->allUnitsHaveAccessToBankAccount($unitIds, 1),
            $message . ' should not have access to bank account'
        );
    }

    public function testExceptionIsThrownIfBankAccountDoesNotExist() : void
    {
        $bankAccounts = \Mockery::mock(IBankAccountRepository::class);
        $bankAccounts->shouldReceive('find')
            ->withArgs([5])
            ->andThrow(new BankAccountNotFound());

        $this->expectException(BankAccountNotFound::class);

        (new BankAccountAccessChecker($bankAccounts, \Mockery::mock(IUnitResolver::class)))
            ->allUnitsHaveAccessToBankAccount([1, 2, 3], 5);
    }

    /**
     * @param int $unitIds
     */
    private function createBankAccount(int $unitId, bool $allowedForSubunits) : BankAccount
    {
        $accountNumber = \Helpers::createAccountNumber();

        $bankAccount = new BankAccount($unitId, 'B', $accountNumber, null, new \DateTimeImmutable(), $this->unitResolver);

        if ($allowedForSubunits) {
            $bankAccount->allowForSubunits();
        }

        return $bankAccount;
    }
}
