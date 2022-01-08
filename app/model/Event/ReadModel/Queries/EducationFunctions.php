<?php

declare(strict_types=1);

namespace Model\Event\ReadModel\Queries;

use Model\Event\SkautisEducationId;

/**
 * @see EducationFunctionsQueryHandler
 */
final class EducationFunctions
{
    private SkautisEducationId $educationId;

    public function __construct(SkautisEducationId $educationId)
    {
        $this->educationId = $educationId;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
