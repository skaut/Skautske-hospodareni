<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\CampCategory;

interface ICampCategoryRepository
{
    /** @return CampCategory[] */
    public function findForCamp(int $campId): array;
}
