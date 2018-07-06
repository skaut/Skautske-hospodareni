<?php

declare(strict_types=1);

namespace Model\Payment\ReadModel\Queries;

class NextVariableSymbolSequenceQuery
{
    /** @var int */
    private $unitId;

    /** @var \DateTimeImmutable */
    private $now;

    public function __construct(int $unitId, \DateTimeImmutable $now)
    {
        $this->unitId = $unitId;
        $this->now    = $now;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }

    public function getNow() : \DateTimeImmutable
    {
        return $this->now;
    }
}
