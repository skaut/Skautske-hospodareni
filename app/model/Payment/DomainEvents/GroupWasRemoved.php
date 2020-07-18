<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

final class GroupWasRemoved
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
