<?php

declare(strict_types=1);

namespace Model\Travel\Commands\Vehicle;

use Model\Travel\Handlers\Vehicle\CreateVehicleHandler;

/** @see CreateVehicleHandler */
final class CreateVehicle
{
    public function __construct(
        private string $type,
        private int $unitId,
        private int|null $subunitId = null,
        private string $registration,
        private float $consumption,
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

    public function getSubunitId(): int|null
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

    public function getUserId(): int
    {
        return $this->userId;
    }
}
