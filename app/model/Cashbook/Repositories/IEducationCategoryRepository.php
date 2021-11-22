<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\EducationCategory;

interface IEducationCategoryRepository
{
    /**
     * @return EducationCategory[]
     */
    public function findForEducation(int $educationId): array;
}
