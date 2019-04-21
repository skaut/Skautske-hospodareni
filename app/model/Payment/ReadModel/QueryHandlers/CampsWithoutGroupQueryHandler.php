<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Event\Camp;
use Model\Event\ReadModel\Queries\CampListQuery;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;
use function array_map;
use function assert;
use function in_array;

final class CampsWithoutGroupQueryHandler
{
    /** @var QueryBus */
    private $queryBus;

    /** @var IGroupRepository */
    private $groups;

    public function __construct(QueryBus $queryBus, IGroupRepository $groups)
    {
        $this->queryBus = $queryBus;
        $this->groups   = $groups;
    }

    /**
     * @return Camp[]
     */
    public function __invoke(CampsWithoutGroupQuery $query) : array
    {
        $camps = $this->queryBus->handle(new CampListQuery($query->getYear()));

        $campWithGroupIds  = $this->getCampWithGroupIds($camps);
        $campsWithoutGroup = [];

        foreach ($camps as $camp) {
            assert($camp instanceof Camp);

            $campId = $camp->getId()->toInt();

            if (in_array($campId, $campWithGroupIds, true)) {
                continue;
            }

            $campsWithoutGroup[$campId] = $camp;
        }

        return $campsWithoutGroup;
    }

    /**
     * @param Camp[] $camps
     * @return int[]
     */
    private function getCampWithGroupIds(array $camps) : array
    {
        $skautisEntities = array_map(
            function (Camp $camp) : SkautisEntity {
                return SkautisEntity::fromCampId($camp->getId());
            },
            $camps
        );

        return array_map(
            function (Group $group) : int {
                return $group->getObject()->getId();
            },
            $this->groups->findBySkautisEntities(...$skautisEntities)
        );
    }
}
