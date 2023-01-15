<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Group;

final class ChangeGroupUnits
{
    /** @param int[] $unitIds */
    public function __construct(private int $groupId, private array $unitIds)
    {
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /** @return int[] */
    public function getUnitIds(): array
    {
        return $this->unitIds;
    }
}
