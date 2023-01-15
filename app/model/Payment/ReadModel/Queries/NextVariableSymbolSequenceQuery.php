<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

use DateTimeImmutable;

class NextVariableSymbolSequenceQuery
{
    public function __construct(private int $unitId, private DateTimeImmutable $now)
    {
    }

    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function getNow(): DateTimeImmutable
    {
        return $this->now;
    }
}
