<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\MoveChitsDialog;
use Model\Cashbook\Cashbook\CashbookId;

interface IMoveChitsDialogFactory
{
    public function create (CashbookId $cashbookId) : MoveChitsDialog;
}
