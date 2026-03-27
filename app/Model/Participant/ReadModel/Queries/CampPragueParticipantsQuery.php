<?php

declare(strict_types=1);

namespace App\Model\Cashbook\ReadModel\Queries;

use App\Model\Event\SkautisCampId;
use Cake\Chronos\ChronosDate;

/** @see CampPragueParticipantStatisticsQueryHandler */
final class CampPragueParticipantsQuery
{
    public function __construct(private SkautisCampId $id, private string $registrationNumber, private ChronosDate $startDate)
    {
    }

    public function getId(): SkautisCampId
    {
        return $this->id;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function getStartDate(): ChronosDate
    {
        return $this->startDate;
    }
}
