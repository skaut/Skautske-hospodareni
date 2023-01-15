<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class EventParticipantsWithoutPaymentQuery
{
    public function __construct(private int $groupId)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
