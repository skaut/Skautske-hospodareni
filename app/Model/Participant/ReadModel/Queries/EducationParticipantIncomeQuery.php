<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Event\SkautisEducationId;

/** @see EducationParticipantIncomeQueryHandler */
final class EducationParticipantIncomeQuery
{
    private SkautisEducationId $educationId;

    public function __construct(SkautisEducationId $eventId)
    {
        $this->educationId = $eventId;
    }

    public function getEducationId(): SkautisEducationId
    {
        return $this->educationId;
    }
}
