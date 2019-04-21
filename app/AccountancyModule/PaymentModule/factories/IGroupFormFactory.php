<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\GroupForm;
use Model\Common\UnitId;
use Model\Payment\Group\SkautisEntity;

interface IGroupFormFactory
{
    public function create(UnitId $unitId, ?SkautisEntity $skautisEntity, ?int $groupId = null) : GroupForm;
}
