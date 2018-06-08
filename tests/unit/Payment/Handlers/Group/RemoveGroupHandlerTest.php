<?php

declare(strict_types=1);

namespace Model\Payment\Handlers\Group;

use Codeception\Test\Unit;
use Mockery as m;
use Model\Payment\Commands\Group\RemoveGroup;
use Model\Payment\Group;
use Model\Payment\GroupNotClosedException;
use Model\Payment\Repositories\IGroupRepository;

final class RemoveGroupHandlerTest extends Unit
{

    public function testAttemptToRemoveOpenGroupThrowsException(): void
    {
        $groupRepository = m::mock(IGroupRepository::class);

        $groupId = 123;

        $groupRepository->shouldReceive('find')
            ->once()
            ->with($groupId)
            ->andReturn(m::mock(Group::class, [
                'getId' => 123,
                'getState' => Group::STATE_OPEN,
            ]));

        $handler = new RemoveGroupHandler($groupRepository);

        $this->expectException(GroupNotClosedException::class);

        $handler->handle(new RemoveGroup($groupId));
    }

    public function testRemovalOfGroupRaisesEvent(): void
    {
        $groupRepository = m::mock(IGroupRepository::class);

        $groupId = 123;
        $group = m::mock(Group::class, [
            'getId' => 123,
            'getState' => Group::STATE_CLOSED,
        ]);

        $groupRepository->shouldReceive('find')
            ->once()
            ->with($groupId)
            ->andReturn($group);

        $groupRepository->shouldReceive('remove')
            ->once()
            ->with($group);

        $handler = new RemoveGroupHandler($groupRepository);

        $handler->handle(new RemoveGroup($groupId));
    }

}
