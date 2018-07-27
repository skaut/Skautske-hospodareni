<?php

declare(strict_types=1);

namespace Model\Payment;

use Model\Unit\UnitHasNoParent;

interface IUnitResolver
{
    /**
     * @throws UnitHasNoParent
     */
    public function getOfficialUnitId(int $unitId) : int;
}
