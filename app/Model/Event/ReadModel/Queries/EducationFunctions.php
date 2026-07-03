<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\SkautisEducationId;

/** @see EducationFunctionsQueryHandler */
final class EducationFunctions
{
    public function __construct(private SkautisEducationId $educationId)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
