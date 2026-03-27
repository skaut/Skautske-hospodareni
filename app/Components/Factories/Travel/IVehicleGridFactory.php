<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\VehicleGrid;

interface IVehicleGridFactory
{
    public function create(int $unitId): VehicleGrid;
}
