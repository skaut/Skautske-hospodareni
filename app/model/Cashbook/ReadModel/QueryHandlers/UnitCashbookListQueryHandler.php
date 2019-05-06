<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Doctrine\ORM\EntityManager;
use Model\Cashbook\ReadModel\Queries\UnitCashbookListQuery;
use Model\Cashbook\Unit;
use Model\Cashbook\Unit\Cashbook;
use Model\DTO\Cashbook\UnitCashbook;
use function array_map;

final class UnitCashbookListQueryHandler
{
    /** @var EntityManager */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @return UnitCashbook[]
     */
    public function __invoke(UnitCashbookListQuery $query) : array
    {
        $unit = $this->entityManager->find(Unit::class, $query->getUnitId());

        if ($unit === null) {
            return [];
        }

        return array_map(function (Cashbook $cashbook) : UnitCashbook {
            return new UnitCashbook($cashbook->getId(), $cashbook->getCashbookId(), $cashbook->getYear());
        }, $unit->getCashbooks());
    }
}
