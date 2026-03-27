<?php

declare(strict_types=1);

namespace App\Model\Unit\ReadModel\QueryHandlers;

use App\Model\Unit\ReadModel\Queries\SubunitListQuery;
use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;

final class SubunitListQueryHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    /** @return Unit[] */
    public function __invoke(SubunitListQuery $query): array
    {
        return $this->units->findByParent($query->getUnitId()->toInt());
    }
}
