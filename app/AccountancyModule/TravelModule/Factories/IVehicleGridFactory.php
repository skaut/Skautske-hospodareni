<?php

namespace App\AccountancyModule\TravelModule\Factories;

use App\AccountancyModule\TravelModule\Components\VehicleGrid;

interface IVehicleGridFactory
{

    public function create(int $unitId) : VehicleGrid;

}
