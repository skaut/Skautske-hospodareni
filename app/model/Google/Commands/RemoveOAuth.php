<?php

declare(strict_types=1);

namespace Model\Google\Commands;

/**
 * @see RemoveOAuthHandler
 */
final class RemoveOAuth
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
