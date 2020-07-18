<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Unit;

use Model\Common\UnitId;

final class ActivateCashbook
{
    private UnitId $unitId;

    private int $cashbookId;

    public function __construct(UnitId $unitId, int $cashbookId)
    {
        $this->unitId     = $unitId;
        $this->cashbookId = $cashbookId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getCashbookId() : int
    {
        return $this->cashbookId;
    }
}
