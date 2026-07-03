<?php

declare(strict_types=1);

namespace App\Model\Event\Commands\Event;

final class UpdateFunctions
{
    public function __construct(
        private int $eventId,
        private ?int $leaderId,
        private ?int $assistantId,
        private ?int $accountantId,
        private ?int $medicId,
    ) {
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
