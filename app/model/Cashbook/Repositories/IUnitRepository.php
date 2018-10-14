<?php

declare(strict_types=1);

namespace Model\Cashbook\Repositories;

use Model\Cashbook\Exception\UnitNotFound;
use Model\Cashbook\Unit;
use Model\Common\UnitId;

interface IUnitRepository
{
    /**
     * @throws UnitNotFound
     */
    public function find(UnitId $id) : Unit;

    public function save(Unit $unit) : void;
}
