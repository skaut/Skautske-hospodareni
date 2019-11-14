<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\DTO\Participant\NonMemberParticipant;
use Model\Event\SkautisCampId;

/**
 * @see CreateEventParticipantHandler
 */
final class CreateCampParticipant
{
    /** @var SkautisCampId */
    private $campId;

    /** @var NonMemberParticipant */
    private $participant;

    public function __construct(SkautisCampId $campId, NonMemberParticipant $participant)
    {
        $this->campId      = $campId;
        $this->participant = $participant;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }

    public function getParticipant() : NonMemberParticipant
    {
        return $this->participant;
    }
}
