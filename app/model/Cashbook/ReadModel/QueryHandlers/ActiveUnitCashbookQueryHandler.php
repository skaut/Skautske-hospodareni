<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\Cashbook\ReadModel\Queries\ActiveUnitCashbookQuery;
use Model\Cashbook\Unit;
use Model\DTO\Cashbook\UnitCashbook;

final class ActiveUnitCashbookQueryHandler
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function __invoke(ActiveUnitCashbookQuery $query) : ?UnitCashbook
    {
        $unit = $this->entityManager->find(Unit::class, $query->getUnitId());

        if ($unit === null) {
            return null;
        }

        $cashbook = $unit->getActiveCashbook();

        return new UnitCashbook($cashbook->getId(), $cashbook->getCashbookId(), $cashbook->getYear());
    }
}
