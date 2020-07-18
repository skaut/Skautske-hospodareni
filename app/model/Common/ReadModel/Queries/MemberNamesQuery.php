<?php

declare(strict_types=1);

namespace Model\Common\ReadModel\Queries;

use Model\Common\UnitId;

final class MemberNamesQuery
{
    private UnitId $unitId;

    private int $minimalAge;

    public function __construct(UnitId $unitId, int $minimalAge)
    {
        $this->unitId     = $unitId;
        $this->minimalAge = $minimalAge;
    }

    public function getUnitId() : UnitId
    {
        return $this->unitId;
    }

    public function getMinimalAge() : int
    {
        return $this->minimalAge;
    }
}
