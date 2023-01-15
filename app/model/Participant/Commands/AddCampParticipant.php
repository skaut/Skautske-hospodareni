<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Event\SkautisCampId;

/** @see AddEventParticipantHandler */
final class AddCampParticipant
{
    public function __construct(private SkautisCampId $campId, private int $personId)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

    public function getPersonId(): int
    {
        return $this->personId;
    }
}
