<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\QueryHandlers;

use App\Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use App\Model\Cashbook\Unit;
use App\Model\DTO\Cashbook\UnitCashbook;
use Doctrine\ORM\EntityManager;

final class ActiveUnitCashbookQueryHandler
{
    public function __construct(private EntityManager $entityManager)
    {
    }

    public function __invoke(ActiveUnitCashbookQuery $query): ?UnitCashbook
    {
        $unit = $this->entityManager->find(Unit::class, $query->getUnitId());

        if ($unit === null) {
            return null;
        }

        $cashbook = $unit->getActiveCashbook();

        return new UnitCashbook($cashbook->getId(), $cashbook->getCashbookId(), $cashbook->getYear());
    }
}
