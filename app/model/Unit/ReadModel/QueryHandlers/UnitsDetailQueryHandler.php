<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\QueryHandlers;

use Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;

class UnitsDetailQueryHandler
{
    private IUnitRepository $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    /**
     * @return Unit[]
     */
    public function __invoke(UnitsDetailQuery $query) : array
    {
        $units = [];
        foreach ($query->getUnitIds() as $unitId) {
            $units[$unitId] = $this->units->find($unitId);
        }

        return $units;
    }
}
