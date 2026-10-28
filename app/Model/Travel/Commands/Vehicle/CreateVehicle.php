<?php

declare(strict_types=1);

namespace App\Model\Travel\Commands\Vehicle;

use App\Model\Travel\Handlers\Vehicle\CreateVehicleHandler;
use App\Model\Travel\Vehicle\FuelType;

/** @see CreateVehicleHandler */
final class CreateVehicle
{
    public function __construct(
        private string $type,
        private int $unitId,
        private ?int $subunitId,
        private string $registration,
        private float $consumption,
        private FuelType $fuelType,
        private int $userId,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getSubunitId(): ?int
    {
        return $this->subunitId;
    }

    public function getRegistration(): string
    {
        return $this->registration;
    }

    public function getConsumption(): float
    {
        return $this->consumption;
    }

    public function getFuelType(): FuelType
    {
        return $this->fuelType;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }
}
