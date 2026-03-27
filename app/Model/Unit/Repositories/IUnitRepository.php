<?php

declare(strict_types=1);

namespace App\Model\Unit\Repositories;

use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;

interface IUnitRepository
{
    /** @return Unit[] */
    public function findByParent(int $parentId): array;

    /** @throws UnitNotFound */
    public function find(int $id): Unit;
}
