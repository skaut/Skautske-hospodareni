<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\MoveChitsDialog;

interface IMoveChitsDialogFactory
{

    public function create(int $cashbookId): MoveChitsDialog;

}
