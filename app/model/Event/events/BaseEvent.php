<?php

namespace Model\Events\Events;

class BaseEvent
{
    /** @var int */
    private $unitId;

    /** @var int */
    private $userId;

    /** @var string */
    private $userName;

    /** @var int */
    private $eventId;

    /** @var string */
    private $eventName;

    public function __construct(int $unitId, int $userId, string $userName, int $eventId, string $eventName)
    {
        $this->unitId = $unitId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->eventId = $eventId;
        $this->eventName = $eventName;
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

    public function getEventId(): int
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

}
