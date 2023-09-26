<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\QueryHandlers;

use Model\Cashbook\ReadModel\Queries\EducationInstructorListQuery;
use Model\Common\Repositories\IInstructorRepository;
use Model\DTO\Instructor\Instructor;

final class EducationInstructorListQueryHandler
{
    public function __construct(private IInstructorRepository $instructors)
    {
    }

    /** @return Instructor[] */
    public function __invoke(EducationInstructorListQuery $query): array
    {
        return $this->instructors->findByEducation($query->getEducationId());
    }
}
