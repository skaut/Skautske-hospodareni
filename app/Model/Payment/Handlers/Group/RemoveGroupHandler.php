<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Group;

use App\Model\Payment\Commands\Group\RemoveGroup;
use App\Model\Payment\Group;
use App\Model\Payment\GroupNotClosed;
use App\Model\Payment\Repositories\IGroupRepository;

use function sprintf;

final class RemoveGroupHandler
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    public function __invoke(RemoveGroup $command): void
    {
        $group = $this->groups->find($command->getGroupId());

        if ($group->getState() !== Group::STATE_CLOSED) {
            throw new GroupNotClosed(sprintf('Cannot remove open group #%d', $group->getId()));
        }

        $this->groups->remove($group);
    }
}
