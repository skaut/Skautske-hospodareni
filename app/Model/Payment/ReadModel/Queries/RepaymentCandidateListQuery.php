<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\RepaymentCandidateListQueryHandler;

/** @see RepaymentCandidateListQueryHandler */
final class RepaymentCandidateListQuery
{
    public function __construct(private int $groupId)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }
}
