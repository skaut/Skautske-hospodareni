<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Group;

use Model\Payment\Handlers\Group\RemoveGroupHandler;

/**
 * @see RemoveGroupHandler
 */
final class RemoveGroup
{
    private int $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }
}
