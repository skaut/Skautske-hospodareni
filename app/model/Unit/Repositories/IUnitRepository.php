<?php

namespace Model\Unit\Repositories;

use Model\Unit\Unit;
use Model\Unit\UnitNotFoundException;

interface IUnitRepository
{

    /**
     * @return Unit[]
     */
    public function findByParent(int $parentId): array;

    /**
     * @throws UnitNotFoundException
     */
    public function find(int $id): Unit;

    /**
     * @deprecated Use IUnitRepository::find()
     */
    public function findAsStdClass(int $id): \stdClass;

}
