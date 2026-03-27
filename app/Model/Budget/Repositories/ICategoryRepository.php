<?php

declare(strict_types=1);

namespace App\Model\Budget\Repositories;

use App\Model\Budget\CategoryNotFound;
use App\Model\Budget\Unit\Category;
use App\Model\Cashbook\Operation;

interface ICategoryRepository
{
    /** @throws CategoryNotFound */
    public function find(int $id): Category;

    /** @return Category[] */
    public function findCategories(int $unitId, Operation $operationType): array;

    public function save(Category $vehicle): void;
}
