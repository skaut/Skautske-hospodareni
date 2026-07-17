<?php

declare(strict_types=1);

namespace App\Model\Payment\ReadModel\QueryHandlers;

use App\Model\Common\Services\QueryBus;
use App\Model\Event\Camp;
use App\Model\Event\ReadModel\Queries\CampListQuery;
use App\Model\Payment\Group;
use App\Model\Payment\Group\SkautisEntity;
use App\Model\Payment\ReadModel\Queries\CampsWithoutGroupQuery;
use App\Model\Payment\Repositories\IGroupRepository;
use LogicException;

use function array_map;
use function in_array;

final class CampsWithoutGroupQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IGroupRepository $groups)
    {
    }

    /** @return Camp[] */
    public function __invoke(CampsWithoutGroupQuery $query): array
    {
        $camps = $this->queryBus->handle(new CampListQuery($query->getYear()));

        $campWithGroupIds = $this->getCampWithGroupIds($camps);
        $campsWithoutGroup = [];

        foreach ($camps as $camp) {
            if (! $camp instanceof Camp) {
                throw new LogicException('Assertion failed.');
            }
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
     *
     * @return int[]
     */
    private function getCampWithGroupIds(array $camps): array
    {
        $skautisEntities = array_map(
            function (Camp $camp): SkautisEntity {
                return SkautisEntity::fromCampId($camp->getId());
            },
            $camps,
        );

        return array_map(
            function (Group $group): int {
                return $group->getObject()->getId();
            },
            $this->groups->findBySkautisEntities(...$skautisEntities),
        );
    }
}
