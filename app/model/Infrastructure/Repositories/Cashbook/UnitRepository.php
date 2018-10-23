<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Doctrine\ORM\NoResultException;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\Repositories\IUnitRepository;
use Model\Cashbook\Unit;
use Model\Common\UnitId;
use Model\Infrastructure\Repositories\AggregateRepository;

final class UnitRepository extends AggregateRepository implements IUnitRepository
{
    public function find(UnitId $id) : Unit
    {
        $unit = $this->getEntityManager()->find(Unit::class, $id);

        if ($unit === null) {
            throw UnitNotFound::withId($id);
        }

        return $unit;
    }

    public function findByCashbookId(CashbookId $cashbookId) : Unit
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

    public function save(Unit $unit) : void
    {
        $this->saveAndDispatchEvents($unit);
    }
}
