<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Unit;

use App\Model\Cashbook\Handlers\Unit\CreateCashbookHandler;
use App\Model\Common\UnitId;

/** @see CreateCashbookHandler */
final class CreateCashbook
{
    public function __construct(private UnitId $unitId, private int $year)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getYear(): int
    {
        return $this->year;
    }
}
