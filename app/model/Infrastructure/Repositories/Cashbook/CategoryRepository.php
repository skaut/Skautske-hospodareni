<?php

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Category;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\ICategoryRepository;
use Model\Infrastructure\Repositories\AbstractRepository;

final class CategoryRepository extends AbstractRepository implements ICategoryRepository
{

    public function findByObjectType(ObjectType $type): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from(Category::class, 'c')
            ->join('c.types', 't')
            ->where('t.type = :type')
            ->orderBy('c.priority', 'DESC')
            ->setParameter('type', $type->getValue())
            ->getQuery()
            ->getResult();
    }

}
