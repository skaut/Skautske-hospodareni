<?php

declare(strict_types=1);

namespace App\Components\Factories\Education;

use App\Components\Education\PrivilegesDialog;

interface IPrivilegesDialogFactory
{
    public function create(int $eventId, ?int $grantId): PrivilegesDialog;
}
