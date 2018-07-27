<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Category;
use Model\Cashbook\CategoryNotFound;
use Model\Cashbook\ObjectType;

/**
 * Loads categories that are stored in hskauting
 */
interface IStaticCategoryRepository
{
    /**
     * @return Category[]
     */
    public function findByObjectType(ObjectType $type) : array;

    /**
     * @throws CategoryNotFound
     */
    public function find(int $id) : Category;
}
