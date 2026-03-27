<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Cashbook;

use App\Model\DTO\Participant\NonMemberParticipant;
use App\Model\Event\SkautisCampId;

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
