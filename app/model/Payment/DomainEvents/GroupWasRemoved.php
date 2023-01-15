<?php

declare(strict_types=1);

namespace Model\Payment\DomainEvents;

final class GroupWasRemoved
{
    public function __construct(private int $groupId)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
