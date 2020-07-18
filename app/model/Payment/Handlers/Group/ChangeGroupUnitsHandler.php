<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\BankAccountNotFound;
use Model\Payment\Commands\Group\ChangeGroupUnits;
use Model\Payment\GroupNotFound;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Services\IBankAccountAccessChecker;
use Model\Payment\Services\IMailCredentialsAccessChecker;

class ChangeGroupUnitsHandler
{
    private IGroupRepository $groups;

    private IBankAccountAccessChecker $bankAccountAccessChecker;

    private IMailCredentialsAccessChecker $mailCredentaccessChecker;

    public function __construct(
        IGroupRepository $groups,
        IBankAccountAccessChecker $bankAccountAccessChecker,
        IMailCredentialsAccessChecker $mailCredentialsAccessChecker
    ) {
        $this->groups                   = $groups;
        $this->bankAccountAccessChecker = $bankAccountAccessChecker;
        $this->mailCredentaccessChecker = $mailCredentialsAccessChecker;
    }

    /**
     * @throws GroupNotFound
     * @throws BankAccountNotFound
     */
    public function __invoke(ChangeGroupUnits $command) : void
    {
        $group = $this->groups->find($command->getGroupId());

        $group->changeUnits(
            $command->getUnitIds(),
            $this->bankAccountAccessChecker,
            $this->mailCredentaccessChecker,
        );

        $this->groups->save($group);
    }
}
