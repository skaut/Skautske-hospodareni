<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

final class UpdateFunctions
{
    private int $eventId;

    private ?int $leaderId = null;

    private ?int $assistantId = null;

    private ?int $accountantId = null;

    private ?int $medicId = null;

    public function __construct(int $eventId, ?int $leaderId, ?int $assistantId, ?int $accountantId, ?int $medicId)
    {
        $this->eventId      = $eventId;
        $this->leaderId     = $leaderId;
        $this->assistantId  = $assistantId;
        $this->accountantId = $accountantId;
        $this->medicId      = $medicId;
    }

    public function getEventId() : int
    {
        return $this->eventId;
    }

    public function getLeaderId() : ?int
    {
        return $this->leaderId;
    }

    public function getAssistantId() : ?int
    {
        return $this->assistantId;
    }

    public function getAccountantId() : ?int
    {
        return $this->accountantId;
    }

    public function getMedicId() : ?int
    {
        return $this->medicId;
    }
}
