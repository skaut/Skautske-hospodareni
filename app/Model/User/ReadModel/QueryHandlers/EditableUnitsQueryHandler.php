<?php

declare(strict_types=1);

namespace App\Model\User\ReadModel\QueryHandlers;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;
use App\Model\User\ReadModel\Queries\EditableUnitsQuery;

final class EditableUnitsQueryHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    /** @return Unit[] */
    public function __invoke(EditableUnitsQuery $query): array
    {
        $role = $query->getRole();

        if (! ($role->isLeader() || $role->isAccountant() || $role->isEventManager() || $role->isEducationLeader() || $role->isEducationAccountant())) {
            return [];
        }

        if ($role->isBasicUnit() || $role->isTroop()) {
            return $this->getUnitTree($role->getUnitId());
        }

        return [
            $role->getUnitId() => $this->units->find($role->getUnitId()),
        ];
    }

    /** @return Unit[] */
    private function getUnitTree(int $rootUnitId): array
    {
        $rootUnit = $this->units->find($rootUnitId);
        $subUnits = $this->units->findByParent($rootUnitId);

        $units = [$rootUnitId => $rootUnit];

        foreach ($subUnits as $subUnit) {
            foreach ($this->getUnitTree($subUnit->getId()) as $id => $unit) {
                $units[$id] = $unit;
            }
        }

        return $units;
    }
}
