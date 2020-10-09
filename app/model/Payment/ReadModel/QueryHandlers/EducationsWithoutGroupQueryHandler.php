<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\QueryHandlers;

use eGen\MessageBus\Bus\QueryBus;
use Model\Event\Education;
use Model\Event\ReadModel\Queries\EducationListQuery;
use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\ReadModel\Queries\EducationsWithoutGroupQuery;
use Model\Payment\Repositories\IGroupRepository;
use function array_map;
use function assert;
use function in_array;

final class EducationsWithoutGroupQueryHandler
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
     * @return Education[]
     */
    public function __invoke(EducationsWithoutGroupQuery $query) : array
    {
        $educations = $this->queryBus->handle(new EducationListQuery($query->getYear()));

        $educationWithGroupIds  = $this->getEducationWithGroupIds($educations);
        $educationsWithoutGroup = [];

        foreach ($educations as $education) {
            assert($education instanceof Education);

            $educationId = $education->getId()->toInt();

            if (in_array($educationId, $educationWithGroupIds, true)) {
                continue;
            }

            $educationsWithoutGroup[$educationId] = $education;
        }

        return $educationsWithoutGroup;
    }

    /**
     * @param Education[] $educations
     *
     * @return int[]
     */
    private function getEducationWithGroupIds(array $educations) : array
    {
        $skautisEntities = array_map(
            fn(Education $education) => SkautisEntity::fromEducationId($education->getId()),
            $educations,
        );

        return array_map(
            fn(Group $group) => $group->getObject()->getId(),
            $this->groups->findBySkautisEntities(...$skautisEntities),
        );
    }
}
