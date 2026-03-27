<?php

declare(strict_types=1);

namespace App\Model\Common\ReadModel\Queries;

use App\Model\Common\UnitId;

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
