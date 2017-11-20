<?php

namespace Model\Unit\Services;

use Model\Payment\IUnitResolver;
use Model\Unit\Repositories\IUnitRepository;

final class UnitResolver implements IUnitResolver
{

    /** @var IUnitRepository */
    private $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    public function getOfficialUnitId(int $unitId): int
    {
        $unit = $this->units->find($unitId, TRUE);

        if($unit->isOfficial()) {
            return $unitId;
        }

        return $this->getOfficialUnitId($unit->getParentId());
    }

}
