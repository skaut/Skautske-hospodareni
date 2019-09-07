<?php

declare(strict_types=1);

namespace Model\Payment;

class UnitResolverStub implements IUnitResolver
{
    /** @var int[] */
    private $officialUnits = [];

    public function getOfficialUnitId(int $unitId) : int
    {
        return $this->officialUnits[$unitId];
    }

    /**
     * @param int[] $officialUnits
     */
    public function setOfficialUnits(array $officialUnits) : void
    {
        $this->officialUnits = $officialUnits;
    }
}
