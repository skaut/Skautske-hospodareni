<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Cashbook;

/**
 * @see RemoveEventParticipantHandler
 */
final class RemoveCampParticipant
{
    /** @var int */
    private $participantId;

    public function __construct(int $participantId)
    {
        $this->participantId = $participantId;
    }

    public function getParticipantId() : int
    {
        return $this->participantId;
    }
}
