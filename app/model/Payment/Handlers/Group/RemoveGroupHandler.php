<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\Commands\Group\RemoveGroup;
use Model\Payment\Group;
use Model\Payment\GroupNotClosedException;
use Model\Payment\Repositories\IGroupRepository;

final class RemoveGroupHandler
{

    /** @var IGroupRepository */
    private $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function handle(RemoveGroup $command): void
    {
        $group = $this->groups->find($command->getGroupId());

        if ($group->getState() !== Group::STATE_CLOSED) {
            throw new GroupNotClosedException(sprintf('Cannot remove open group #%d', $group->getId()));
        }

        $this->groups->remove($group);
    }

}
