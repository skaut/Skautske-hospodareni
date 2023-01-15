<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property-read int $id
 * @property-read string $type
 * @property-read string $registration
 * @property-read string $label
 * @property-read int|NULL $subunitId
 * @property-read float $consumption
 * @property-read bool $archived
 * @property-read DateTimeImmutable $createdAt
 * @property-read string $authorName
 */
class Vehicle
{
    use SmartObject;

    public function __construct(
        private int $id,
        private string $type,
        private int $unitId,
        private string $registration,
        private string $label,
        private int|null $subunitId = null,
        private float $consumption,
        private bool $archived,
        private DateTimeImmutable $createdAt,
        private string $authorName,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getRegistration(): string
    {
        return $this->registration;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getSubunitId(): int|null
    {
        return $this->subunitId;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }

    public function isArchived(): bool
    {
        return $this->archived;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }
}
