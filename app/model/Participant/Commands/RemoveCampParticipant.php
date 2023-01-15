<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

/** @see RemoveEventParticipantHandler */
final class RemoveCampParticipant
{
    public function __construct(private int $participantId)
    {
    }

    public function getParticipantId(): int
    {
        return $this->participantId;
    }
}
