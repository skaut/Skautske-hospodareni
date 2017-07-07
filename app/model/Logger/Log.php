<?php

namespace Model\Logger;

class Log
{

    /** @var int */
    private $id;

    /** @var int */
    private $unitId;

    /** @var \DateTimeImmutable */
    private $date;

    /** @var int */
    private $userId;

    /** @var string */
    private $description;

    /** @var int|NULL */
    private $objectId;

    public function __construct(int $unitId, int $userId, string $desc, ?int $objectId = NULL)
    {
        $this->unitId = $unitId;
        $this->date = new \DateTimeImmutable();
        $this->userId = $userId;
        $this->description = $desc;
        $this->objectId = $objectId;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getObjectId(): ?int
    {
        return $this->objectId;
    }

}
