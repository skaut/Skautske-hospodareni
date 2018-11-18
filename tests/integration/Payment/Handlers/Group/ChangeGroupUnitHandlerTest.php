<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\BankAccount;
use Model\Payment\Commands\Group\ChangeGroupUnit;
use Model\Payment\Group;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\UnitResolverStub;

class ChangeGroupUnitHandlerTest extends \CommandHandlerTest
{
    /** @var UnitResolverStub */
    private $unitResolver;

    /** @var IGroupRepository */
    private $groups;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    protected function _before() : void
    {
        $this->tester->useConfigFiles(['Payment/Handlers/Group/ChangeGroupUnitHandlerTest.neon']);
        parent::_before();
        $this->unitResolver = $this->tester->grabService(UnitResolverStub::class);
        $this->groups       = $this->tester->grabService(IGroupRepository::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
    }

    /**
     * @return string[]
     */
    protected function getTestedEntites() : array
    {
        return [
            BankAccount::class,
            Group::class,
            Group\Email::class,
        ];
    }

    /**
     * @throws \Exception
     */
    public function testChangeUnitForGroupWithoutBankAccount() : void
    {
        $this->createGroup(10, null);

        $this->commandBus->handle(new ChangeGroupUnit(1, 20));

        $group = $this->groups->find(1);
        $this->assertSame(20, $group->getUnitId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeOfficialUnitToSubunitWithBankAccountAllowedForSubunitsPreservesBankAccount() : void
    {
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            20 => 20,
        ]);
        $bankAccount =$this->createBankAccount(20, true);
        $this->createGroup(20, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 10));

        $group = $this->groups->find(1);
        $this->assertSame(10, $group->getUnitId());
        $this->assertSame(1, $group->getBankAccountId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeOfficialUnitToSubunitWithBankAccountThatIsNotAllowedForSubunits() : void
    {
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            20 => 20,
        ]);
        $bankAccount = $this->createBankAccount(20, false);
        $this->createGroup(20, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 10));

        $group = $this->groups->find(1);
        $this->assertSame(10, $group->getUnitId());
        $this->assertNull($group->getBankAccountId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeUnitWithBankAccountToUnitInSameUnit() : void
    {
        $this->unitResolver->setOfficialUnits([
            10 => 30,
            20 => 30,
            30 => 30,
        ]);
        $bankAccount = $this->createBankAccount(30, true);
        $this->createGroup(10, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 20));

        $group = $this->groups->find(1);
        $this->assertSame(20, $group->getUnitId());
        $this->assertSame(1, $group->getBankAccountId());
    }

    private function createGroup(int $unitId, ?BankAccount $bankAccount) : void
    {
        $emails          = \Helpers::createEmails();
        $paymentDefaults = new Group\PaymentDefaults(null, null, null, null);

        $group = new Group($unitId, null, 'Group', $paymentDefaults, new \DateTimeImmutable(), $emails, null, $bankAccount);

        $this->groups->save($group);
    }

    private function createBankAccount(int $unitId, bool $allowedForSubunits) : BankAccount
    {
        $accountNumber = \Helpers::createAccountNumber();

        $bankAccount = new BankAccount($unitId, 'B', $accountNumber, null, new \DateTimeImmutable(), $this->unitResolver);

        if ($allowedForSubunits) {
            $bankAccount->allowForSubunits();
        }

        $this->bankAccounts->save($bankAccount);

        return $bankAccount;
    }
}
