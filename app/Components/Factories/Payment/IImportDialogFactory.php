<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\ImportDialog;

interface IImportDialogFactory
{
    public function create(int $groupId): ImportDialog;
}
