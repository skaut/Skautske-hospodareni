<?php

declare(strict_types=1);

namespace App\Components\Camps;

interface IPrivilegesDialogFactory
{
    public function create(int $campId): PrivilegesDialog;
}
