<?php

declare(strict_types=1);

namespace App\Components\Factories\Camps;

use App\Components\Camps\PrivilegesDialog;

interface IPrivilegesDialogFactory
{
    public function create(int $campId): PrivilegesDialog;
}
