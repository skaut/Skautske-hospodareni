<?php

declare(strict_types=1);

namespace App\Model\Event\ReadModel\Queries;

use App\Model\Event\ReadModel\QueryHandlers\EducationParticipantParticipationStatsQueryHandler;

/** @see EducationParticipantParticipationStatsQueryHandler */
final class EducationParticipantParticipationStatsQuery
{
    public function __construct(private int $grantId)
    {
    }

    public function getGrantId(): int
    {
        return $this->grantId;
    }
}
