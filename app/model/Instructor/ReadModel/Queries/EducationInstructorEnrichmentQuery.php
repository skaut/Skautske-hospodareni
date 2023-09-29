<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\EducationInstructorEnrichmentQueryHandler;
use Model\DTO\Instructor\Instructor;

/** @see EducationInstructorEnrichmentQueryHandler */
final class EducationInstructorEnrichmentQuery
{
    private Instructor $instructor;

    public function __construct(Instructor $id)
    {
        $this->instructor = $id;
    }

    public function getInstructor(): Instructor
    {
        return $this->instructor;
    }
}
