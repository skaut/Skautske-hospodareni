<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Factories;

use App\AccountancyModule\TravelModule\Components\RoadworthyControl;

interface IRoadworthyControlFactory
{
    public function create(int $vehicleId, bool $isEditable) : RoadworthyControl;
}
