<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\CampCategory;

interface ICampCategoryRepository
{
    /**
     * @return CampCategory[]
     */
    public function findForCamp(int $campId) : array;
}
