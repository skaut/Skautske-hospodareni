<?php

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\MassAddForm;

interface IMassAddFormFactory
{

    public function create(int $groupId): MassAddForm;

}
