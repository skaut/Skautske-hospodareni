<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\ImportDialog;

interface IImportDialogFactory
{
    public function create(int $groupId): ImportDialog;
}
