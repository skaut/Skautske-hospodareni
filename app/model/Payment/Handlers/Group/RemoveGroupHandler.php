<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Model\Payment\Commands\Group\RemoveGroup;
use Model\Payment\Group;
use Model\Payment\GroupNotClosed;
use Model\Payment\Repositories\IGroupRepository;
use function sprintf;

final class RemoveGroupHandler
{
    private IGroupRepository $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    public function __invoke(RemoveGroup $command) : void
    {
        $group = $this->groups->find($command->getGroupId());

        if ($group->getState() !== Group::STATE_CLOSED) {
            throw new GroupNotClosed(sprintf('Cannot remove open group #%d', $group->getId()));
        }

        $this->groups->remove($group);
    }
}
