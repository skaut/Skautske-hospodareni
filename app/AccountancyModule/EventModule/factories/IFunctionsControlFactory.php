<?php

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\FunctionsControl;

interface IFunctionsControlFactory
{

    public function create(int $eventId) : FunctionsControl;

}
