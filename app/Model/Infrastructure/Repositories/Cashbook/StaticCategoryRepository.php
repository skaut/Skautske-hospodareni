<?php

declare(strict_types=1);

namespace App\Model\Infrastructure\Repositories\Cashbook;

use App\Model\Cashbook\Category;
use App\Model\Cashbook\CategoryNotFound;
use App\Model\Cashbook\ObjectType;
use App\Model\Cashbook\Repositories\IStaticCategoryRepository;
use App\Model\Infrastructure\Repositories\AggregateRepository;

final class StaticCategoryRepository extends AggregateRepository implements IStaticCategoryRepository
{
    /** @return Category[] */
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
            ->setCacheable(true)
            ->getResult();
    }

    /** @throws CategoryNotFound */
    public function find(int $id): Category
    {
        $category = $this->getEntityManager()->find(Category::class, $id);

        if ($category === null) {
            throw new CategoryNotFound();
        }

        return $category;
    }
}
