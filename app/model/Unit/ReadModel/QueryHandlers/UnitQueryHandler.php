<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\QueryHandlers;

use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;

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
