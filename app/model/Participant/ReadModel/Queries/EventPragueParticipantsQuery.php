<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Cake\Chronos\ChronosDate;
use Model\Event\SkautisEventId;

/** @see EventPragueParticipantsQueryHandler */
final class EventPragueParticipantsQuery
{
    public function __construct(private SkautisEventId $id, private string $registrationNumber, private ChronosDate $startDate)
    {
    }

    public function getId(): SkautisEventId
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
