<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\QueryHandlers;

use Model\Unit\ReadModel\Queries\SubunitListQuery;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;

final class SubunitListQueryHandler
{
    private IUnitRepository $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    /**
     * @return Unit[]
     */
    public function __invoke(SubunitListQuery $query) : array
    {
        return $this->units->findByParent($query->getUnitId()->toInt());
    }
}
