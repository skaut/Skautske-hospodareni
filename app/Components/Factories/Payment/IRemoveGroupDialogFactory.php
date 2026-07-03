<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\RemoveGroupDialog;

interface IRemoveGroupDialogFactory
{
    public function create(int $groupId, bool $isAllowed): RemoveGroupDialog;
}
