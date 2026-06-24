<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\GroupForm;
use App\Model\Common\UnitId;
use App\Model\Payment\Group\SkautisEntity;

interface IGroupFormFactory
{
    public function create(
        UnitId $unitId,
        ?SkautisEntity $skautisEntity,
        ?int $groupId = null,
        ?int $cloneSourceGroupId = null,
    ): GroupForm;
}
