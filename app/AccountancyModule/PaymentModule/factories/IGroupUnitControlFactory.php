<?php

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\GroupUnitControl;

interface IGroupUnitControlFactory
{

    public function create(int $groupId) : GroupUnitControl;

}
