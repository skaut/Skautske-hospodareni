<?php

namespace Model\Payment;

use Model\Unit\UnitHasNoParentException;

interface IUnitResolver
{

    /**
     * @throws UnitHasNoParentException
     */
    public function getOfficialUnitId(int $unitId): int;

}
