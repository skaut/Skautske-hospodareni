<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use eGen\MessageBus\Bus\EventBus;
use Model\Payment\Commands\Group\RemoveGroup;
use Model\Payment\DomainEvents\GroupWasRemoved;
use Model\Payment\Group;
use Model\Payment\GroupNotClosedException;
use Model\Payment\Repositories\IGroupRepository;

final class RemoveGroupHandler
{

    /** @var IGroupRepository */
    private $groups;

    /** @var EventBus */
    private $eventBus;

    public function __construct(IGroupRepository $groups, EventBus $eventBus)
    {
        $this->groups = $groups;
        $this->eventBus = $eventBus;
    }

    public function handle(RemoveGroup $command): void
    {
        $group = $this->groups->find($command->getGroupId());

        if ($group->getState() !== Group::STATE_CLOSED) {
            throw new GroupNotClosedException(sprintf('Cannot remove open group #%d', $group->getId()));
        }

        $this->eventBus->handle(new GroupWasRemoved($command->getGroupId()));
        $this->groups->remove($group);
    }

}
