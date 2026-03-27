<?php

declare(strict_types=1);

namespace App\Model\Payment\Commands\Group;

use App\Model\Payment\Handlers\Group\RemoveGroupHandler;

/** @see RemoveGroupHandler */
final class RemoveGroup
{
    public function __construct(private int $groupId)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
