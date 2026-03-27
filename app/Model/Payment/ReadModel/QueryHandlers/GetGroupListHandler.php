<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\DTO\Payment\Group;
use App\Model\DTO\Payment\GroupFactory;
use App\Model\Payment\ReadModel\Queries\GetGroupList;
use App\Model\Payment\Repositories\IGroupRepository;

use function array_map;

final class GetGroupListHandler
{
    public function __construct(private IGroupRepository $groups)
    {
    }

    /** @return Group[] */
    public function __invoke(GetGroupList $query): array
    {
        $groups = $this->groups->findByUnits($query->getUnitIds(), $query->onlyOpen());

        return array_map([GroupFactory::class, 'create'], $groups);
    }
}
