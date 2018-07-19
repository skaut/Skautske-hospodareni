<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Category;
use Model\Cashbook\CategoryNotFound;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\Infrastructure\Repositories\AggregateRepository;

final class StaticCategoryRepository extends AggregateRepository implements IStaticCategoryRepository
{
    /**
     * @return Category[]
     */
    public function findByObjectType(ObjectType $type) : array
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

    /**
     * @throws CategoryNotFound
     */
    public function find(int $id) : Category
    {
        $category = $this->getEntityManager()->find(Category::class, $id);

        if ($category === null) {
            throw new CategoryNotFound();
        }

        return $category;
    }
}
