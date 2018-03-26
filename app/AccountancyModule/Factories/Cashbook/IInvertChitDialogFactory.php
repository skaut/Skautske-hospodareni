<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\InvertChitDialog;

interface IInvertChitDialogFactory
{

    public function create(int $cashbookId): InvertChitDialog;

}
