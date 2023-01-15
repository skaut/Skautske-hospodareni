<?php

declare(strict_types=1);

namespace Model\Common\ReadModel\Queries;

use Model\Common\UnitId;

final class MemberNamesQuery
{
    public function __construct(private UnitId $unitId, private int $minimalAge)
    {
    }

    public function getUnitId(): UnitId
    {
        return $this->unitId;
    }

    public function getMinimalAge(): int
    {
        return $this->minimalAge;
    }
}
