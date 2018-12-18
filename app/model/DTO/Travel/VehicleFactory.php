<?php

declare(strict_types=1);

namespace Model\DTO\Travel;

use Model\Travel\Vehicle as VehicleEntity;

class VehicleFactory
{
    public static function create(VehicleEntity $vehicle) : Vehicle
    {
        return new Vehicle(
            $vehicle->getId(),
            $vehicle->getType(),
            $vehicle->getUnitId(),
            $vehicle->getRegistration(),
            $vehicle->getLabel(),
            $vehicle->getSubunitId(),
            $vehicle->getConsumption(),
            $vehicle->isArchived(),
            $vehicle->getMetadata()->getCreatedAt(),
            $vehicle->getMetadata()->getAuthorName()
        );
    }
}
