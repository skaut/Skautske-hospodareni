<?php

declare(strict_types=1);

namespace Model\Unit\Services;

use Model\Payment\IUnitResolver;
use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\UnitHasNoParent;

final class UnitResolver implements IUnitResolver
{
    private IUnitRepository $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    /**
     * @throws UnitHasNoParent
     */
    public function getOfficialUnitId(int $unitId) : int
    {
        $unit = $this->units->find($unitId);

        if ($unit->isOfficial()) {
            return $unitId;
        }
        if ($unit->getParentId() === null) {
            throw new UnitHasNoParent('Unit ' . $unit->getId() . " doesn't have set parentID");
        }

        return $this->getOfficialUnitId($unit->getParentId());
    }
}
