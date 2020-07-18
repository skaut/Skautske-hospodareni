<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Unit;

use Model\Common\UnitId;

/**
 * @see CreateUnitHandler
 */
final class CreateUnit
{
    private UnitId $unitId;

    private int $activeCashbookYear;

    public function __construct(UnitId $unitId, int $activeCashbookYear)
    {
        $this->unitId             = $unitId;
        $this->activeCashbookYear = $activeCashbookYear;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getActiveCashbookYear() : int
    {
        return $this->activeCashbookYear;
    }
}
