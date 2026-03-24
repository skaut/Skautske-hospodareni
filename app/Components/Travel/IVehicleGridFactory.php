<?php

declare(strict_types=1);

namespace App\Components\Travel;

interface IVehicleGridFactory
{
    public function create(int $unitId): VehicleGrid;
}
