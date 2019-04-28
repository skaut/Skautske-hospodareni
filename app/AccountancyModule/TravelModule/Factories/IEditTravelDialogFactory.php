<?php

declare(strict_types=1);

namespace App\AccountancyModule\TravelModule\Factories;

use App\AccountancyModule\TravelModule\Components\EditTravelDialog;

interface IEditTravelDialogFactory
{
    public function create(int $commandId) : EditTravelDialog;
}
