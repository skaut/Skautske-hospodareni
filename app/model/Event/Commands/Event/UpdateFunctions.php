<?php

declare(strict_types=1);

namespace Model\Event\Commands\Event;

final class UpdateFunctions
{
    public function __construct(private int $eventId, private int|null $leaderId = null, private int|null $assistantId = null, private int|null $accountantId = null, private int|null $medicId = null)
    {
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getLeaderId(): int|null
    {
        return $this->leaderId;
    }

    public function getAssistantId(): int|null
    {
        return $this->assistantId;
    }

    public function getAccountantId(): int|null
    {
        return $this->accountantId;
    }

    public function getMedicId(): int|null
    {
        return $this->medicId;
    }
}
