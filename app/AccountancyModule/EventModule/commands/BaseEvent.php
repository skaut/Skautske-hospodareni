<?php

namespace App\AccountancyModule\EventModule\Commands;

class BaseEvent
{
    /** @var int */
    protected $unitId;

    /** @var int */
    protected $userId;

    /** @var string */
    protected $userName;

    /** @var int */
    protected $localId;

    /** @var string */
    protected $eventName;

    public function __construct(int $unitId, int $userId, string $userName, int $localId, $eventName)
    {
        $this->unitId = $unitId;
        $this->userId = $userId;
        $this->userName = $userName;
        $this->localId = $localId;
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

    public function getLocalId(): int
    {
        return $this->localId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

}
