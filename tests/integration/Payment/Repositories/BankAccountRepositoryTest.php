<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Mockery as m;
use Model\Payment\BankAccount;
use Model\Payment\BankAccountNotFoundException;
use Model\Payment\IUnitResolver;
use function array_keys;

class BankAccountRepositoryTest extends \IntegrationTest
{
    /** @var BankAccountRepository */
    private $repository;

    public function getTestedEntites() : array
    {
        return [
            BankAccount::class,
        ];
    }

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['config/doctrine.neon']);
        parent::_before();
        $this->repository = new BankAccountRepository($this->entityManager);
    }

    public function testSaveSetsId() : void
    {
        $account = $this->createAccount();

        $this->repository->save($account);

        $this->assertSame(1, $account->getId());
    }

    public function testSavedAccountIsInDatabase() : void
    {
        $createdAt = new \DateTimeImmutable();
        $account   = $this->createAccount(5, $createdAt);

        $this->repository->save($account);

        $this->tester->seeInDatabase('pa_bank_account', $this->getRow(1, 5, $createdAt));
    }

    public function testFindSavedAccount() : void
    {
        $this->tester->haveInDatabase('pa_bank_account', $this->getRow(1, 5));
        $account = $this->createAccount();
        $this->repository->save($account);
        $this->entityManager->clear();

        $foundAccount = $this->repository->find(1);

        $this->assertInstanceOf(BankAccount::class, $foundAccount);
    }

    public function testFindNotSavedAccountThrowsException() : void
    {
        $this->expectException(BankAccountNotFoundException::class);

        $this->repository->find(1);
    }

    public function testFindByUnit() : void
    {
        $unitId = 5;
        $this->tester->haveInDatabase('pa_bank_account', $this->getRow(1, $unitId));
        $this->tester->haveInDatabase('pa_bank_account', $this->getRow(2, 6));
        $this->tester->haveInDatabase('pa_bank_account', $this->getRow(3, $unitId));

        $accounts = $this->repository->findByUnit(5);

        $this->assertCount(2, $accounts);

        $this->assertInstanceOf(BankAccount::class, $accounts[0]);
        $this->assertInstanceOf(BankAccount::class, $accounts[1]);

        $this->assertSame(1, $accounts[0]->getId());
        $this->assertSame(1, $accounts[0]->getId());
    }

    public function testRemove() : void
    {
        $notDeletedRow = $this->getRow(1, 5);
        $row           = $this->getRow(2, 5);
        $this->tester->haveInDatabase('pa_bank_account', $notDeletedRow);
        $this->tester->haveInDatabase('pa_bank_account', $row);

        $account = $this->repository->find(2);
        $this->repository->remove($account);

        $this->tester->seeInDatabase('pa_bank_account', $notDeletedRow);
        $this->tester->dontSeeInDatabase('pa_bank_account', $row);
    }

    public function testFindByIds() : void
    {
        $rows = [
            $this->getRow(1, 2),
            $this->getRow(2, 2),
            $this->getRow(3, 3),
        ];
        foreach ($rows as $row) {
            $this->tester->haveInDatabase('pa_bank_account', $row);
        }
        $ids = [1, 3];

        $accounts = $this->repository->findByIds($ids);

        $this->assertCount(2, $accounts);
        $this->assertSame($ids, array_keys($accounts));

        foreach ($ids as $id) {
            $this->assertSame($id, $accounts[$id]->getId());
        }
    }

    public function testFindByIdsWithOneAccountThatDoesntExist() : void
    {
        $rows = [
            $this->getRow(1, 2),
            $this->getRow(2, 2),
        ];
        foreach ($rows as $row) {
            $this->tester->haveInDatabase('pa_bank_account', $row);
        }
        $ids = [1, 3];

        $this->expectException(BankAccountNotFoundException::class);

        $this->repository->findByIds($ids);
    }

    public function testFindByIdsWithEmptyArgument() : void
    {
        $accounts = $this->repository->findByIds([]);

        $this->assertSame([], $accounts);
    }

    private function createAccount($unitId = 1, ?\DateTimeImmutable $createdAt = null) : BankAccount
    {
        return new BankAccount(
            1,
            'Hlavní',
            new BankAccount\AccountNumber(null, '2000942144', '2010'),
            'test-token',
            $createdAt ?? new \DateTimeImmutable(),
            m::mock(IUnitResolver::class, ['getOfficialUnitId' => $unitId])
        );
    }

    private function getRow(int $id, int $unitId, ?\DateTimeImmutable $createdAt = null)
    {
        return [
            'id' => $id,
            'unit_id' => $unitId,
            'name' => 'Hlavní',
            'token' => 'test-token',
            'created_at' => ($createdAt ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'allowed_for_subunits' => 0,
            'number_prefix' => null,
            'number_number' => '2000942144',
            'number_bank_code' => '2010',
        ];
    }
}
