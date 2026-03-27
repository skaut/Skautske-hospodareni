<?php

declare(strict_types=1);

namespace App\Model\Common\Repositories;

use App\Model\Common\Member;
use App\Model\Common\UnitId;

interface IMemberRepository
{
    /** @return Member[] */
    public function findByUnit(UnitId $unitId, bool $includeSubunitMembers): array;
}
