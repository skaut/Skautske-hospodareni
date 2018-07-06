<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\RemoveGroupDialog;

interface IRemoveGroupDialogFactory
{
    public function create (int $groupId) : RemoveGroupDialog;
}
