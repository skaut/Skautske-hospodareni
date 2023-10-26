<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Model\Cashbook\ReadModel\QueryHandlers\EducationParticipantCategoryIdQueryHandler;
use Model\Event\SkautisEducationId;

/** @see EducationParticipantCategoryIdQueryHandler */
final class EducationParticipantCategoryIdQuery
{
    public function __construct(private SkautisEducationId $educationId, private int $year)
    {
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
