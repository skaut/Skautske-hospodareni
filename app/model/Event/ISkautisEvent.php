<?php

declare(strict_types=1);

namespace Model\Skautis;

use Model\Common\UnitId;

interface ISkautisEvent
{
    public function getUnitId() : UnitId;
}
