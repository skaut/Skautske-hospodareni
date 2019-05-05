<?php

declare(strict_types=1);

namespace Model\Common\Repositories;

use Model\Common\Member;
use Model\Common\UnitId;

interface IMemberRepository
{
    /**
     * @return Member[]
     */
    public function findByUnit(UnitId $unitId, bool $includeSubunitMembers) : array;
}
