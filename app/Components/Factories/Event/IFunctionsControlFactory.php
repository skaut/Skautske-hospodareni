<?php

declare(strict_types=1);

namespace App\Components\Factories\Event;

use App\Components\Event\FunctionsControl;
use App\Model\Common\UnitId;

interface IFunctionsControlFactory
{
    public function create(int $eventId, UnitId $unitId): FunctionsControl;
}
