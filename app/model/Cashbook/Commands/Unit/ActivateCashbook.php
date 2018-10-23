<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Unit;

use Model\Common\UnitId;

final class ActivateCashbook
{
    /** @var UnitId */
    private $unitId;

    /** @var int */
    private $cashbookId;

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
