<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\BankAccountNotFound;
use Model\Payment\Commands\Group\ChangeGroupUnit;
use Model\Payment\GroupNotFound;
use Model\Payment\Repositories\IGroupRepository;
use Model\Payment\Services\IBankAccountAccessChecker;

class ChangeGroupUnitHandler
{
    /** @var IGroupRepository */
    private $groups;

    /** @var IBankAccountAccessChecker */
    private $accessChecker;

    public function __construct(IGroupRepository $groups, IBankAccountAccessChecker $accessChecker)
    {
        $this->groups        = $groups;
        $this->accessChecker = $accessChecker;
    }

    /**
     * @throws GroupNotFound
     * @throws BankAccountNotFound
     */
    public function handle(ChangeGroupUnit $command) : void
    {
        $group = $this->groups->find($command->getGroupId());

        $group->changeUnit($command->getUnitId(), $this->accessChecker);

        $this->groups->save($group);
    }
}
