<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Group;

use App\Model\Payment\BankAccountNotFound;
use App\Model\Payment\Commands\Group\ChangeGroupUnits;
use App\Model\Payment\GroupNotFound;
use App\Model\Payment\Repositories\IGroupRepository;
use App\Model\Payment\Services\IBankAccountAccessChecker;
use App\Model\Payment\Services\IOAuthAccessChecker;

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
