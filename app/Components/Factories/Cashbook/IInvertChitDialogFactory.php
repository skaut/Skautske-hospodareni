<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\Cashbook\InvertChitDialog;
use App\Model\Cashbook\Cashbook\CashbookId;

interface IInvertChitDialogFactory
{
    public function create(CashbookId $cashbookId): InvertChitDialog;
}
