<?php

namespace Model\Unit\Repositories;

use Model\Unit\Unit;

interface IUnitRepository
{

    /**
     * @return Unit[]
     */
    public function findByParent(int $parentId): array;

    public function find(int $id);

}
