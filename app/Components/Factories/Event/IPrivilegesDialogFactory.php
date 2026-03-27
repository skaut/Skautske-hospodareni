<?php

declare(strict_types=1);

namespace App\Components\Factories\Event;

use App\Components\Event\PrivilegesDialog;

interface IPrivilegesDialogFactory
{
    public function create(int $eventId, bool $isDraft): PrivilegesDialog;
}
