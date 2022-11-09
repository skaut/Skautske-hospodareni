<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\PaymentListQueryHandler;

/** @see PaymentListQueryHandler */
final class PaymentListQuery
{
    private int $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
