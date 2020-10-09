<?php

declare(strict_types=1);

namespace Model\Chit\Events;

class BaseChit
{
    private int $unitId;

    private int $userId;

    private string $userName;

    private int $chitId;

    private int $eventId;

    public function __construct(int $unitId, int $userId, string $userName, int $chitId, int $eventId)
    {
        $this->unitId   = $unitId;
        $this->userId   = $userId;
        $this->userName = $userName;
        $this->chitId   = $chitId;
        $this->eventId  = $eventId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function getUserName() : string
    {
        return $this->userName;
    }

    public function getChitId() : int
    {
        return $this->chitId;
    }

    public function getEventId() : int
    {
        return $this->eventId;
    }
}
