<?php

declare(strict_types=1);

namespace App\Components\Travel;

interface IEditTravelDialogFactory
{
    public function create(int $commandId): EditTravelDialog;
}
