<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\Queries;

use App\Model\Payment\ReadModel\QueryHandlers\RecentlyVisitedGroupsQueryHandler;

/** @see RecentlyVisitedGroupsQueryHandler */
final class RecentlyVisitedGroupsQuery
{
    /** @param int[] $unitIds */
    public function __construct(
        private int $userId,
        private array $unitIds,
        private int $limit,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }
}
