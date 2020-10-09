<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

/**
 * @see RemoveEventParticipantHandler
 */
final class RemoveEventParticipant
{
    private int $participantId;

    public function __construct(int $participantId)
    {
        $this->participantId = $participantId;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }
}
