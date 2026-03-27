<?php

declare(strict_types=1);

namespace App\Model\Unit\ReadModel\QueryHandlers;

use App\Model\Unit\ReadModel\Queries\UnitsDetailQuery;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;

class UnitsDetailQueryHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    /** @return Unit[] */
    public function __invoke(UnitsDetailQuery $query): array
    {
        $units = [];
        foreach ($query->getUnitIds() as $unitId) {
            $units[$unitId] = $this->units->find($unitId);
        }

        return $units;
    }
}
