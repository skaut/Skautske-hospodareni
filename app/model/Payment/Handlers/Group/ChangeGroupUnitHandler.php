<?php

namespace Model\Payment\Handlers\Group;

use Model\Payment\Commands\Group\ChangeGroupUnit;
use Model\Payment\IUnitResolver;
use Model\Payment\Repositories\IBankAccountRepository;
use Model\Payment\Repositories\IGroupRepository;

class ChangeGroupUnitHandler
{

    /** @var IGroupRepository */
    private $groups;

    /** @var IBankAccountRepository */
    private $bankAccounts;

    /** @var IUnitResolver */
    private $unitResolver;

    public function __construct(IGroupRepository $groups, IBankAccountRepository $bankAccounts, IUnitResolver $unitResolver)
    {
        $this->groups = $groups;
        $this->bankAccounts = $bankAccounts;
        $this->unitResolver = $unitResolver;
    }

    /**
     * @throws \Model\Payment\GroupNotFoundException
     * @throws \Model\Payment\BankAccountNotFoundException
     */
    public function handle(ChangeGroupUnit $command): void
    {
        $group = $this->groups->find($command->getGroupId());

        $group->changeUnit(
            $command->getUnitId(),
            $this->unitResolver,
            $this->bankAccounts
        );

        $this->groups->save($group);
    }

}
