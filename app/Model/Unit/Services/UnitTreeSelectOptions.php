<?php

declare(strict_types=1);

namespace App\Model\Unit\Services;

use App\Model\Unit\Repositories\IUnitRepository;
use App\Model\Unit\Unit;

use function str_repeat;

/**
 * Units for an administrator to choose from, down to střediska. Every level costs one skautIS call per unit,
 * and listing the troops too meant thousands of calls in a row, which timed out whenever the unit cache was empty.
 */
final class UnitTreeSelectOptions
{
    public const ROOT_UNIT_ID = 23200;

    public function __construct(private IUnitRepository $units)
    {
    }

    /**
     * @param int|null $selectedUnitId added at the end when it lies below the střediska, so the form can keep it selected
     *
     * @return array<int, string>
     */
    public function getOptions(int $rootUnitId = self::ROOT_UNIT_ID, ?int $selectedUnitId = null): array
    {
        $options = $this->getUnitOptions($this->units->find($rootUnitId), 0);

        if ($selectedUnitId !== null && ! isset($options[$selectedUnitId])) {
            $selected = $this->units->find($selectedUnitId);
            $options[$selected->getId()] = $selected->getRegistrationNumber().' '.$selected->getDisplayName();
        }

        return $options;
    }

    /** @return array<int, string> */
    private function getUnitOptions(Unit $unit, int $level): array
    {
        $options = [
            $unit->getId() => str_repeat('   ', $level).$unit->getRegistrationNumber().' '.$unit->getDisplayName(),
        ];

        if (! $unit->hasOfficialChildren()) {
            return $options;
        }

        foreach ($this->units->findByParent($unit->getId()) as $child) {
            $options += $this->getUnitOptions($child, $level + 1);
        }

        return $options;
    }
}
