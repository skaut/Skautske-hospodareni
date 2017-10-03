<?php

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

    protected function _before()
    {
        $this->tester->useConfigFiles(['Payment/Handlers/Group/ChangeGroupUnitHandlerTest.neon']);
        parent::_before();
        $this->unitResolver = $this->tester->grabService(UnitResolverStub::class);
        $this->groups = $this->tester->grabService(IGroupRepository::class);
        $this->bankAccounts = $this->tester->grabService(IBankAccountRepository::class);
    }

    protected function getTestedEntites(): array
    {
        return [
            BankAccount::class,
            Group::class,
        ];
    }

    /**
     * @throws \Exception
     */
    public function testChangeUnitForGroupWithoutBankAccount()
    {
        $this->createGroup(10, NULL);

        $this->commandBus->handle(new ChangeGroupUnit(1, 20));

        $group = $this->groups->find(1);
        $this->assertSame(20, $group->getUnitId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeOfficialUnitToSubunitWithBankAccountAllowedForSubunitsPreservesBankAccount()
    {
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            20 => 20,
        ]);
        $bankAccount =$this->createBankAccount(20, TRUE);
        $this->createGroup(20, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 10));

        $group = $this->groups->find(1);
        $this->assertSame(10, $group->getUnitId());
        $this->assertSame(1, $group->getBankAccountId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeOfficialUnitToSubunitWithBankAccountThatIsNotAllowedForSubunits()
    {
        $this->unitResolver->setOfficialUnits([
            10 => 20,
            20 => 20,
        ]);
        $bankAccount = $this->createBankAccount(20, FALSE);
        $this->createGroup(20, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 10));


        $group = $this->groups->find(1);
        $this->assertSame(10, $group->getUnitId());
        $this->assertNull($group->getBankAccountId());
    }

    /**
     * @throws \Exception
     */
    public function testChangeUnitWithBankAccountToUnitInSameUnit()
    {
        $this->unitResolver->setOfficialUnits([
            10 => 30,
            20 => 30,
            30 => 30,
        ]);
        $bankAccount = $this->createBankAccount(30, TRUE);
        $this->createGroup(10, $bankAccount);

        $this->commandBus->handle(new ChangeGroupUnit(1, 20));

        $group = $this->groups->find(1);
        $this->assertSame(20, $group->getUnitId());
        $this->assertSame(1, $group->getBankAccountId());
    }

    private function createGroup(int $unitId, ?BankAccount $bankAccount): void
    {
        $group = new Group(
            $unitId,
            NULL,
            'Group',
            NULL,
            NULL,
            NULL,
            NULL,
            new \DateTimeImmutable(),
            new Group\EmailTemplate('', ''),
            NULL,
            $bankAccount
        );

        $this->groups->save($group);
    }

    private function createBankAccount(int $unitId, bool $allowedForSubunits): BankAccount
    {
        $bankAccount = new BankAccount(
            $unitId,
            'B',
            \Helpers::createAccountNumber(),
            NULL,
            new \DateTimeImmutable(),
            $this->unitResolver
        );

        if($allowedForSubunits) {
            $bankAccount->allowForSubunits();
        }

        $this->bankAccounts->save($bankAccount);

        return $bankAccount;
    }

}
