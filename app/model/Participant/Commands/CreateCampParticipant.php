<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\DTO\Participant\NonMemberParticipant;
use Model\Event\SkautisCampId;

/** @see CreateEventParticipantHandler */
final class CreateCampParticipant
{
    public function __construct(private SkautisCampId $campId, private NonMemberParticipant $participant)
    {
    }

    public function getCampId(): SkautisCampId
    {
        return $this->campId;
    }

    public function getParticipant(): NonMemberParticipant
    {
        return $this->participant;
    }
}
