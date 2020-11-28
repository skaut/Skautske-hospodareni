<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use Model\Payment\ReadModel\QueryHandlers\RepaymentCandidateListQueryHandler;

/**
 * @see RepaymentCandidateListQueryHandler
 */
final class RepaymentCandidateListQuery
{
    /** @var int */
    private $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }
}
