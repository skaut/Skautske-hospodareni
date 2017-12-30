<?php

namespace Model\Event\Commands\Event;

final class UpdateFunctions
{

    /** @var int */
    private $eventId;

    /** @var int|NULL */
    private $leaderId;

    /** @var int|NULL */
    private $assistantId;

    /** @var int|NULL */
    private $accountantId;

    /** @var int|NULL */
    private $medicId;

    public function __construct(int $eventId, ?int $leaderId, ?int $assistantId, ?int $accountantId, ?int $medicId)
    {
        $this->eventId = $eventId;
        $this->leaderId = $leaderId;
        $this->assistantId = $assistantId;
        $this->accountantId = $accountantId;
        $this->medicId = $medicId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getLeaderId(): ?int
    {
        return $this->leaderId;
    }

    public function getAssistantId(): ?int
    {
        return $this->assistantId;
    }

    public function getAccountantId(): ?int
    {
        return $this->accountantId;
    }

    public function getMedicId(): ?int
    {
        return $this->medicId;
    }

}
