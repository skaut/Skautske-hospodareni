<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Cake\Chronos\ChronosDate;
use Model\Event\SkautisCampId;

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
