<?php

declare(strict_types=1);

namespace App\Model\Skautis;

use App\Model\Common\UnitId;

interface ISkautisEvent
{
    public function getUnitId(): UnitId;
}
