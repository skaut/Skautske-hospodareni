<?php

declare(strict_types=1);

namespace Model\Chit\Events;

class BaseChit
{
    public function __construct(private int $unitId, private int $userId, private string $userName, private int $chitId, private int $eventId)
    {
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getChitId(): int
    {
        return $this->chitId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }
}
