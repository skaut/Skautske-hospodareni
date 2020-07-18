<?php

declare(strict_types=1);

namespace Model\Cashbook\Events\Unit;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\UnitId;

final class CashbookWasCreated
{
    private UnitId $unitId;

    private CashbookId $cashbookId;

    public function __construct(UnitId $unitId, CashbookId $cashbookId)
    {
        $this->unitId     = $unitId;
        $this->cashbookId = $cashbookId;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getCashbookId() : CashbookId
    {
        return $this->cashbookId;
    }
}
