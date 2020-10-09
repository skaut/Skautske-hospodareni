<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\DTO\Payment\Group;
use Model\DTO\Payment\GroupFactory;
use Model\Payment\ReadModel\Queries\GetGroupList;
use Model\Payment\Repositories\IGroupRepository;
use function array_map;

final class GetGroupListHandler
{
    private IGroupRepository $groups;

    public function __construct(IGroupRepository $groups)
    {
        $this->groups = $groups;
    }

    /**
     * @return Group[]
     */
    public function __invoke(GetGroupList $query) : array
    {
        $groups = $this->groups->findByUnits($query->getUnitIds(), $query->onlyOpen());

        return array_map([GroupFactory::class, 'create'], $groups);
    }
}
