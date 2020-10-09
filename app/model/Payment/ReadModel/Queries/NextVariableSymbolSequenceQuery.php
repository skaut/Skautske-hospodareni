<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use DateTimeImmutable;

class NextVariableSymbolSequenceQuery
{
    private int $unitId;

    private DateTimeImmutable $now;

    public function __construct(int $unitId, DateTimeImmutable $now)
    {
        $this->unitId = $unitId;
        $this->now    = $now;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getNow() : DateTimeImmutable
    {
        return $this->now;
    }
}
