<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\FunctionsControl;
use Model\Common\UnitId;

interface IFunctionsControlFactory
{
    public function create(int $eventId, UnitId $unitId) : FunctionsControl;
}
