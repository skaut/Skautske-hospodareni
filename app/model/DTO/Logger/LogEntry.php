<?php

declare(strict_types=1);

namespace Model\DTO\Logger;

use DateTimeImmutable;
use Model\Logger\Log\Type;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read int $unitId
 * @property-read DateTimeImmutable $date
 * @property-read int $userId
 * @property-read Type $type
 * @property-read ?int $typeId
 * @property-read string $description
 */
class LogEntry
{
    use SmartObject;

    private int $unitId;

    private DateTimeImmutable $date;

    private int $userId;

    /** @var  string */
    private $description;

    private Type $type;

    /** @var int|NULL */
    private $typeId;

    public function __construct(
        int $unitId,
        DateTimeImmutable $date,
        int $userId,
        string $description,
        Type $type,
        ?int $typeId
    ) {
        $this->unitId      = $unitId;
        $this->date        = $date;
        $this->userId      = $userId;
        $this->description = $description;
        $this->type        = $type;
        $this->typeId      = $typeId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getDate() : DateTimeImmutable
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

    public function getType() : string
    {
        return $this->type->getValue();
    }

    public function getTypeId() : ?int
    {
        return $this->typeId;
    }
}
