<?php

declare(strict_types=1);

namespace Stubs;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\Unit\UnitNotFound;

final class UnitRepositoryStub implements IUnitRepository
{
    public function findByParent(int $parentId): array
    {
        return [];
    }

    public function find(int $id): Unit
    {
        if ($id <= 0) {
            throw new UnitNotFound();
        }

        return new Unit($id, 'unit-'.$id, 'Jednotka '.$id, '12345678', 'Ulice 1', 'Praha', '11000', '1.01', 'stredisko');
    }
}
