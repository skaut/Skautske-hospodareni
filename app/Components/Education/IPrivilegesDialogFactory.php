<?php

declare(strict_types=1);

namespace App\Components\Education;

interface IPrivilegesDialogFactory
{
    public function create(int $eventId, ?int $grantId): PrivilegesDialog;
}
