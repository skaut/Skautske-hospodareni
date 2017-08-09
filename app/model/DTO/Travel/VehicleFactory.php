<?php

namespace Model\DTO\Travel;

use Model\Travel\Vehicle as VehicleEntity;

class VehicleFactory
{

    public static function create(VehicleEntity $vehicle): Vehicle
    {
        return new Vehicle($vehicle->getId(), $vehicle->getLabel(), $vehicle->isArchived());
    }

}
