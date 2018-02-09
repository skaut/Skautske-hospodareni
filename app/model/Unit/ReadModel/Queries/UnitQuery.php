<?php

declare(strict_types=1);

namespace Model\Unit\ReadModel\Queries;

final class UnitQuery
{

    /** @var int */
    private $unitId;

    public function __construct(int $unitId)
    {
        $this->unitId = $unitId;
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

}
