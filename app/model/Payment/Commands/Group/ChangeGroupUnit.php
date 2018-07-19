<?php

declare(strict_types=1);

namespace Model\Payment\Commands\Group;

final class ChangeGroupUnit
{
    /** @var int */
    private $groupId;

    /** @var int */
    private $unitId;

    public function __construct(int $groupId, int $unitId)
    {
        $this->groupId = $groupId;
        $this->unitId  = $unitId;
    }

    public function getGroupId() : int
    {
        return $this->groupId;
    }

    public function getUnitId() : int
    {
        return $this->unitId;
    }
}
