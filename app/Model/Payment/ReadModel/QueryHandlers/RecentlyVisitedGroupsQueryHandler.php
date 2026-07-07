<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\GroupFactory;
use App\Model\Payment\ReadModel\Queries\RecentlyVisitedGroupsQuery;
use App\Model\User\Repository\PaymentGroupVisitRepository;

use function array_map;

final class RecentlyVisitedGroupsQueryHandler
{
    public function __construct(private PaymentGroupVisitRepository $visits)
    {
    }

    /** @return Group[] */
    public function __invoke(RecentlyVisitedGroupsQuery $query): array
    {
        $groups = $this->visits->findRecentlyVisitedGroups(
            $query->getUserId(),
            $query->getUnitIds(),
            $query->getLimit(),
        );

        return array_map([GroupFactory::class, 'create'], $groups);
    }
}
