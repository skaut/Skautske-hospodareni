<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\GroupUnitControl;

interface IGroupUnitControlFactory
{
    public function create(int $groupId): GroupUnitControl;
}
