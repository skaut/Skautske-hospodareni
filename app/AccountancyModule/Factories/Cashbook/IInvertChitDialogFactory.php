<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\InvertChitDialog;
use Model\Cashbook\Cashbook\CashbookId;

interface IInvertChitDialogFactory
{

    public function create(CashbookId $cashbookId) : InvertChitDialog;

}
