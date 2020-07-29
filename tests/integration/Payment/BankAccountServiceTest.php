<?php

declare(strict_types=1);

namespace Tests\Integration\Pairing;

use DateTimeImmutable;
use Helpers;
use IntegrationTest;
use Model\Payment\BankAccount;
use Model\Payment\BankAccountService;
use Model\Payment\Group;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\UnitResolverStub;
use Stubs\BankAccountAccessCheckerStub;
use Stubs\OAuthsAccessCheckerStub;

class BankAccountServiceTest extends IntegrationTest
{
    /** @var BankAccountService */
    private $bankAccountService;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IGroupRepository */
    private $groups;

    /** @var UnitResolverStub */
    private $unitResolver;

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/BankAccountServiceTest.neon']);
        parent::_before();
        $this->bankAccountService = $this->tester->grabService(BankAccountService::class);
        $this->bankAccounts       = $this->tester->grabService(IBankAccountRepository::class);
        $this->groups             = $this->tester->grabService(IGroupRepository::class);
        $this->unitResolver       = $this->tester->grabService(UnitResolverStub::class);
    }

    /**
     * @return string[]
     */
    public function getTestedAggregateRoots() : array
    {
        return [
            BankAccount::class,
            Group::class,
        ];
    }

    public function testDisallowingBankAccountForSubunitsCascadesToGroups() : void
    {
        $this->unitResolver->setOfficialUnits([
            5 => 10,
            10 => 10,
        ]);
        $bankAccount = $this->createBankAccount();
        $bankAccount->allowForSubunits();
        $this->bankAccounts->save($bankAccount);

        $this->addGroup(5, $bankAccount);
        $this->addGroup(5, $bankAccount);
        $this->addGroup(10, $bankAccount); // This one belongs to official unit

        $this->bankAccountService->disallowForSubunits($bankAccount->getId());

        $group1 = $this->groups->find(1); // subunit
        $group2 = $this->groups->find(2); // subunit
        $group3 = $this->groups->find(3);

        $this->assertNull($group1->getBankAccountId());
        $this->assertNull($group2->getBankAccountId());
        $this->assertSame(1, $group3->getBankAccountId());
    }

    private function createBankAccount() : BankAccount
    {
        return new BankAccount(
            5, // official id is resolved to 10
            'Název',
            Helpers::createAccountNumber(),
            null,
            new DateTimeImmutable(),
            $this->unitResolver
        );
    }

    private function addGroup(int $unitId, BankAccount $account) : void
    {
        $paymentDefaults = new Group\PaymentDefaults(null, null, null, null);
        $emails          = Helpers::createEmails();

        $group = new Group(
            [$unitId],
            null,
            'Nazev',
            $paymentDefaults,
            new DateTimeImmutable(),
            $emails,
            null,
            $account,
            new BankAccountAccessCheckerStub(),
            new OAuthsAccessCheckerStub(),
        );

        $this->groups->save($group);
    }
}
