<?php

declare(strict_types=1);

namespace App\Presentation\Travel\Vehicle\Factories;

use App\Presentation\Travel\Vehicle\Components\RoadworthyControl;

interface IRoadworthyControlFactory
{
    public function create(int $vehicleId, bool $isEditable): RoadworthyControl;
}
