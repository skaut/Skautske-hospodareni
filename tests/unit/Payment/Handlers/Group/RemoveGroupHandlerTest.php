<?php

declare(strict_types=1);

namespace App\Model\Payment\Handlers\Group;

use App\Model\Payment\Commands\Group\RemoveGroup;
use App\Model\Payment\Group;
use App\Model\Payment\GroupNotClosed;
use App\Model\Payment\Repositories\IGroupRepository;
use Codeception\Test\Unit;
use Mockery as m;

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

        $this->expectException(GroupNotClosed::class);

        $handler(new RemoveGroup($groupId));
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

        $handler(new RemoveGroup($groupId));
    }
}
