<?php

declare(strict_types=1);

namespace Model\User\ReadModel\QueryHandlers;

use Model\Unit\Repositories\IUnitRepository;
use Model\Unit\Unit;
use Model\User\ReadModel\Queries\EditableUnitsQuery;

final class EditableUnitsQueryHandler
{
    public function __construct(private IUnitRepository $units)
    {
    }

    /** @return Unit[] */
    public function __invoke(EditableUnitsQuery $query): array
    {
        $role = $query->getRole();

        if (! $role->isLeader() && ! $role->isAccountant() && ! $role->isEventManager()) {
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
