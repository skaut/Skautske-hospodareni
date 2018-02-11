<?php

namespace Model\Unit\Services;

use Model\Payment\IUnitResolver;
use Model\Unit\UnitHasNoParentException;
use Model\Unit\Repositories\IUnitRepository;

final class UnitResolver implements IUnitResolver
{

    /** @var IUnitRepository */
    private $units;

    public function __construct(IUnitRepository $units)
    {
        $this->units = $units;
    }

    /**
     * @throws UnitHasNoParentException
     */
    public function getOfficialUnitId(int $unitId): int
    {
        $unit = $this->units->find($unitId);

        if ($unit->isOfficial()) {
            return $unitId;
        }
        if ($unit->getParentId() === NULL) {
            throw new UnitHasNoParentException("Unit " . $unit->getId() . " doesn't have set parentID");
        }

        return $this->getOfficialUnitId($unit->getParentId());
    }

}
