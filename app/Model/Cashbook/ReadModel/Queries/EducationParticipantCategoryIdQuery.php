<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Cashbook\ReadModel\QueryHandlers\EducationParticipantCategoryIdQueryHandler;
use App\Model\Event\SkautisEducationId;

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
