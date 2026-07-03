<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\PaymentListQueryHandler;

/** @see PaymentListQueryHandler */
final class PaymentListQuery
{
    public function __construct(private int $groupId)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
