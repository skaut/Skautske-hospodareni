<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisEducationId;

/** @see EducationQueryHandler */
class EducationQuery
{
    public function __construct(private SkautisEducationId $educationId)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
