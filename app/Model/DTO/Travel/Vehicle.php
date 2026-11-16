<?php

declare(strict_types=1);

namespace App\Model\DTO\Travel;

use App\Model\Travel\Vehicle\FuelType;
use DateTimeImmutable;
use Nette\SmartObject;

/**
 * @property int               $id
 * @property string            $type
 * @property string            $registration
 * @property string            $label
 * @property int|null          $subunitId
 * @property float             $consumption
 * @property FuelType          $fuelType
 * @property bool              $archived
 * @property DateTimeImmutable $createdAt
 * @property string            $authorName
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
        private ?int $subunitId,
        private float $consumption,
        private FuelType $fuelType,
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

    public function getSubunitId(): ?int
    {
        return $this->subunitId;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }

    public function getFuelType(): FuelType
    {
        return $this->fuelType;
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
