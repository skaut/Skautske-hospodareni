<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

use Model\Event\SkautisCampId;

/**
 * @see AddEventParticipantHandler
 */
final class AddCampParticipant
{
    /** @var SkautisCampId */
    private $campId;

    /** @var int */
    private $participantId;

    public function __construct(SkautisCampId $campId, int $participantId)
    {
        $this->campId        = $campId;
        $this->participantId = $participantId;
    }

    public function getCampId() : SkautisCampId
    {
        return $this->campId;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }
}
