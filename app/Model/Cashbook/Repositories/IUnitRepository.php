<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Repositories;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Exception\UnitNotFound;
use App\Model\Cashbook\Unit;
use App\Model\Common\UnitId;

interface IUnitRepository
{
    /** @throws UnitNotFound */
    public function find(UnitId $id): Unit;

    /** @throws UnitNotFound */
    public function findByCashbookId(CashbookId $cashbookId): Unit;

    public function save(Unit $unit): void;
}
