<?php

declare(strict_types=1);

namespace App\Components\Travel;

interface IRoadworthyControlFactory
{
    public function create(int $vehicleId, bool $isEditable): RoadworthyControl;
}
