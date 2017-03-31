<?php

declare(strict_types=1);

namespace Model\Payment\Repositories;

use Model\Payment\Group;
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
     * @param Group $group
     */
    public function save(Group $group): void;

}
