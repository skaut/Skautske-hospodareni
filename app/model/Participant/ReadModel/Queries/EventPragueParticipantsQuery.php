<?php

declare(strict_types=1);

namespace Model\Cashbook\ReadModel\Queries;

use Cake\Chronos\Date;
use Model\Event\SkautisEventId;

/** @see EventPragueParticipantsQueryHandler */
final class EventPragueParticipantsQuery
{
    public function __construct(private SkautisEventId $id, private string $registrationNumber, private Date $startDate)
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

    public function getStartDate(): Date
    {
        return $this->startDate;
    }
}
