<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\BankAccountNotFound;
use Model\Payment\Commands\Group\ChangeGroupUnits;
use Model\Payment\GroupNotFound;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Services\IBankAccountAccessChecker;
use Model\Payment\Services\IOAuthAccessChecker;

class ChangeGroupUnitsHandler
{
    private IOAuthAccessChecker $mailCredentaccessChecker;

    public function __construct(
        private IGroupRepository $groups,
        private IBankAccountAccessChecker $bankAccountAccessChecker,
        IOAuthAccessChecker $oAuthAccessChecker,
    ) {
        $this->mailCredentaccessChecker = $oAuthAccessChecker;
    }

    /**
     * @throws GroupNotFound
     * @throws BankAccountNotFound
     */
    public function __invoke(ChangeGroupUnits $command): void
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
