<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Category;
use Model\Cashbook\CategoryNotFoundException;
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
     * @throws CategoryNotFoundException
     */
    public function find(int $id) : Category;
}
