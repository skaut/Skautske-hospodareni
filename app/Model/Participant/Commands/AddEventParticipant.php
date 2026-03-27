<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\Event\SkautisEventId;

/** @see AddEventParticipantHandler */
final class AddEventParticipant
{
    public function __construct(private SkautisEventId $eventId, private int $personId)
    {
    }

    public function getEventId(): SkautisEventId
    {
        return $this->eventId;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }
}
