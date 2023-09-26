<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\EducationInstructorListQueryHandler;
use Model\Event\SkautisEducationId;

/** @see EducationInstructorListQueryHandler */
final class EducationInstructorListQuery
{
    private SkautisEducationId $educationId;

    public function __construct(SkautisEducationId $id)
    {
        $this->educationId = $id;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
