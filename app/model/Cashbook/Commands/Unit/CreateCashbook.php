<?php

declare(strict_types=1);

namespace Model\Cashbook\Commands\Unit;

use Model\Cashbook\Handlers\Unit\CreateCashbookHandler;
use Model\Common\UnitId;

/**
 * @see CreateCashbookHandler
 */
final class CreateCashbook
{
    /** @var UnitId */
    private $unitId;

    /** @var int */
    private $year;

    public function __construct(UnitId $unitId, int $year)
    {
        $this->unitId = $unitId;
        $this->year   = $year;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getYear() : int
    {
        return $this->year;
    }
}
