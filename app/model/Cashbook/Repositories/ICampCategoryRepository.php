<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\ICategory;

interface ICampCategoryRepository
{

    /**
     * @return ICategory[]
     */
    public function findForCamp(int $campId): array;

}
