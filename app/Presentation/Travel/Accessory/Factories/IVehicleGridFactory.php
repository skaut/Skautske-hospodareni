<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Accessory\Factories;

use App\Presentation\Travel\Accessory\Components\VehicleGrid;

interface IVehicleGridFactory
{
    public function create(int $unitId): VehicleGrid;
}
