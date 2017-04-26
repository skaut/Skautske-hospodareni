<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Model\Payment\Group;
use Model\Payment\Group\SkautisEntity;
use Model\Payment\Group\Type;
use Model\Payment\GroupNotFoundException;

interface IGroupRepository
{

    /**
     * @param int $id
     * @return Group
     * @throws GroupNotFoundException
     */
    public function find(int $id): Group;

    /**
     * @param int[] $unitIds
     * @param bool $openOnly
     * @return Group[]
     */
    public function findByUnits(array $unitIds, bool $openOnly): array;

    /**
     * @param SkautisEntity $object
     * @return Group[]
     */
    public function findBySkautisEntity(SkautisEntity $object): array;

    /**
     * @param Type $type
     * @return Group[]
     */
    public function findBySkautisEntityType(Type $type): array;

    /**
     * @param Group $group
     */
    public function save(Group $group): void;

}
