<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\EducationCategory;

interface IEducationCategoryRepository
{
    /** @return EducationCategory[] */
    public function findForEducation(int $educationId, int $year): array;
}
