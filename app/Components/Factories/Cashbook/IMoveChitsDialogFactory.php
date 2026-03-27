<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\Cashbook\MoveChitsDialog;
use App\Model\Cashbook\Cashbook\CashbookId;

interface IMoveChitsDialogFactory
{
    public function create(CashbookId $cashbookId): MoveChitsDialog;
}
