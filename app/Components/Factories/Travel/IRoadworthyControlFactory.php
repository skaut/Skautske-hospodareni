<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\RoadworthyControl;

interface IRoadworthyControlFactory
{
    public function create(int $vehicleId, bool $isEditable): RoadworthyControl;
}
