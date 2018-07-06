<?php

declare(strict_types=1);

namespace Model\Logger;

use Model\Logger\Log\Type;

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

    /** @var  Type */
    private $type;

    /** @var int|NULL */
    private $typeId;

    public function __construct(int $unitId, int $userId, string $desc, Type $type, ?int $typeId = null)
    {
        $this->unitId      = $unitId;
        $this->date        = new \DateTimeImmutable();
        $this->userId      = $userId;
        $this->description = $desc;
        $this->type        = $type;
        $this->typeId      = $typeId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getDate() : \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserId() : int
    {
        return $this->userId;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function getType() : Type
    {
        return $this->type;
    }

    public function getTypeId() : ?int
    {
        return $this->typeId;
    }
}
