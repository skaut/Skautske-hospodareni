<?php

namespace Model\Chit\Events;

class BaseChit
{
    /** @var int */
    private $unitId;

    /** @var int */
    private $userId;

    /** @var string */
    private $userName;

    /** @var int */
    private $chitId;

    /** @var int */
    private $eventId;

    public function __construct(int $unitId, int $userId, string $userName, int $chitId, int $eventId)
    {
        $this->unitId = $unitId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->chitId = $chitId;
        $this->eventId = $eventId;
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

    public function getChitId(): array
    {
        return $this->chitId;
    }

    public function getEventId(): int
    {
        return $this->eventId;
    }
}
