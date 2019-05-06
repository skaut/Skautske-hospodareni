<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\QueryHandlers;

use Model\Unit\ReadModel\Queries\UnitQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;

class UnitQueryHandler
{
    /** @var IUnitRepository */
    private $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    public function __invoke(UnitQuery $query) : Unit
    {
        return $this->units->find($query->getUnitId());
    }
}
