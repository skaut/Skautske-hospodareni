<?php

declare(strict_types=1);

namespace App\Model\Payment;

use App\Model\Unit\UnitHasNoParent;

interface IUnitResolver
{
    /** @throws UnitHasNoParent */
    public function getOfficialUnitId(int $unitId): int;
}
