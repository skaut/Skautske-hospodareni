<?php

declare(strict_types=1);

namespace Model\Infrastructure\Repositories\Cashbook;

use Model\Cashbook\Category;
use Model\Cashbook\CategoryNotFoundException;
use Model\Cashbook\ObjectType;
use Model\Cashbook\Repositories\IStaticCategoryRepository;
use Model\Infrastructure\Repositories\AbstractRepository;

final class StaticCategoryRepository extends AbstractRepository implements IStaticCategoryRepository
{
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
     * @throws CategoryNotFoundException
     */
    public function find(int $id) : Category
    {
        $category = $this->getEntityManager()->find(Category::class, $id);

        if ($category === null) {
            throw new CategoryNotFoundException();
        }

        return $category;
    }
}
