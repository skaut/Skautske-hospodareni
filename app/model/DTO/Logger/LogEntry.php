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

    public function __construct(
        private int $unitId,
        private DateTimeImmutable $date,
        private int $userId,
        private string $description,
        private Type $type,
        private int|null $typeId = null,
    ) {
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getDate(): DateTimeImmutable
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

    public function getType(): string
    {
        return $this->type->getValue();
    }

    public function getTypeId(): int|null
    {
        return $this->typeId;
    }
}
