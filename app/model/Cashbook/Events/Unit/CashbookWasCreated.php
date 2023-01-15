<?php

declare(strict_types=1);

namespace Model\Cashbook\Events\Unit;

use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\UnitId;

final class CashbookWasCreated
{
    public function __construct(private UnitId $unitId, private CashbookId $cashbookId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getCashbookId(): CashbookId
    {
        return $this->cashbookId;
    }
}
