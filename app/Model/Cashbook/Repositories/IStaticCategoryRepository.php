<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Category;
use App\Model\Cashbook\CategoryNotFound;
use App\Model\Cashbook\ObjectType;

/**
 * Loads categories that are stored in hskauting.
 */
interface IStaticCategoryRepository
{
    /** @return Category[] */
    public function findByObjectType(ObjectType $type): array;

    /** @throws CategoryNotFound */
    public function find(int $id): Category;
}
