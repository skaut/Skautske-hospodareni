<?php

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\Functions;

interface IFunctionsFactory
{

    public function create(int $eventId) : Functions;

}
