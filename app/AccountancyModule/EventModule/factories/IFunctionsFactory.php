<?php

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\Functions;

interface IFunctionsFactory
{

    public function create($eventId) : Functions;

}
