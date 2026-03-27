<?php

declare(strict_types=1);

namespace App\Components\Factories\Travel;

use App\Components\Travel\EditTravelDialog;

interface IEditTravelDialogFactory
{
    public function create(int $commandId): EditTravelDialog;
}
