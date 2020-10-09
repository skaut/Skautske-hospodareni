<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

final class PaymentListQuery
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
