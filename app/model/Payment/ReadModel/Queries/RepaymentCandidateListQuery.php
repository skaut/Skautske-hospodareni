<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\RepaymentCandidateListQueryHandler;

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
