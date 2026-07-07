<?php

declare(strict_types=1);

namespace App\Model\Unit\Services;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;

use function str_repeat;

final class UnitTreeSelectOptions
{
    public const ROOT_UNIT_ID = 23200;

    public function __construct(private IUnitRepository $units)
    {
    }

    /** @return array<int, string> */
    public function getOptions(int $rootUnitId = self::ROOT_UNIT_ID): array
    {
        $root = $this->units->find($rootUnitId);

        return $this->getUnitOptions($root, 0);
    }

    /** @return array<int, string> */
    private function getUnitOptions(Unit $unit, int $level): array
    {
        $options = [
            $unit->getId() => str_repeat('   ', $level).$unit->getRegistrationNumber().' '.$unit->getDisplayName(),
        ];

        foreach ($this->units->findByParent($unit->getId()) as $child) {
            $options += $this->getUnitOptions($child, $level + 1);
        }

        return $options;
    }
}
