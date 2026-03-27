<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Cashbook;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Exception\UnitNotFound;
use App\Model\Cashbook\Repositories\IUnitRepository;
use App\Model\Cashbook\Unit;
use App\Model\Common\UnitId;
use App\Model\Infrastructure\Repositories\AggregateRepository;
use Doctrine\ORM\NoResultException;

final class UnitRepository extends AggregateRepository implements IUnitRepository
{
    public function find(UnitId $id): Unit
    {
        $unit = $this->getEntityManager()->find(Unit::class, $id);

        if ($unit === null) {
            throw UnitNotFound::withId($id);
        }

        return $unit;
    }

    public function findByCashbookId(CashbookId $cashbookId): Unit
    {
        $builder = $this->getEntityManager()->createQueryBuilder();

        try {
            return $builder->select('u')
                ->from(Unit::class, 'u')
                ->join('u.cashbooks', 'c')
                ->where('c.cashbookId = :cashbookId')
                ->setParameter('cashbookId', $cashbookId)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            throw UnitNotFound::forCashbook($cashbookId, $e);
        }
    }

    public function save(Unit $unit): void
    {
        $this->saveAndDispatchEvents($unit);
    }
}
