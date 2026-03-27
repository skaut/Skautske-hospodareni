<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use App\Model\Cashbook\Unit;
use App\Model\Cashbook\Unit\Cashbook;
use App\Model\DTO\Cashbook\UnitCashbook;
use Doctrine\ORM\EntityManager;

use function array_map;

final class UnitCashbookListQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    /** @return UnitCashbook[] */
    public function __invoke(UnitCashbookListQuery $query): array
    {
        $unit = $this->entityManager->find(Unit::class, $query->getUnitId());

        if ($unit === null) {
            return [];
        }

        return array_map(function (Cashbook $cashbook): UnitCashbook {
            return new UnitCashbook($cashbook->getId(), $cashbook->getCashbookId(), $cashbook->getYear());
        }, $unit->getCashbooks());
    }
}
