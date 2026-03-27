<?php

declare(strict_types=1);

namespace App\Model\Cashbook\Commands\Unit;

use App\Model\Common\UnitId;

final class ActivateCashbook
{
    public function __construct(private UnitId $unitId, private int $cashbookId)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getCashbookId(): int
    {
        return $this->cashbookId;
    }
}
