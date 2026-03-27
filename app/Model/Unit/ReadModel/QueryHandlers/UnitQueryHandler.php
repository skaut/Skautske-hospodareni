<?php

declare(strict_types=1);

namespace App\Model\Unit\ReadModel\QueryHandlers;

use App\Model\Unit\ReadModel\Queries\UnitQuery;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;

class UnitQueryHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    public function __invoke(UnitQuery $query): Unit
    {
        return $this->units->find($query->getUnitId());
    }
}
