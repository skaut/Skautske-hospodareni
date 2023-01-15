<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use Model\Common\Services\QueryBus;
use Model\Event\Education;
use Model\Event\ReadModel\Queries\EducationListQuery;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;

use function array_filter;
use function array_map;
use function in_array;

final class EducationsWithoutGroupQueryHandler
{
    public function __construct(private QueryBus $queryBus, private IGroupRepository $groups)
    {
    }

    /** @return array<int, Education> (indexed by ID) */
    public function __invoke(EducationsWithoutGroupQuery $query): array
    {
        $educations = $this->queryBus->handle(new EducationListQuery($query->getYear()));

        $educationWithGroupIds = $this->getEducationWithGroupIds($educations);

        return array_filter(
            $educations,
            fn (Education $education) => ! in_array($education->getId()->toInt(), $educationWithGroupIds, true),
        );
    }

    /**
     * @param Education[] $educations
     *
     * @return int[]
     */
    private function getEducationWithGroupIds(array $educations): array
    {
        $skautisEntities = array_map(
            fn (Education $education) => SkautisEntity::fromEducationId($education->getId()),
            $educations,
        );

        return array_map(
            fn (Group $group) => $group->getObject()->getId(),
            $this->groups->findBySkautisEntities(...$skautisEntities),
        );
    }
}
