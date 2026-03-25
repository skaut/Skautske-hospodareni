<?php

declare(strict_types=1);

namespace App\Components\Event;

interface IPrivilegesDialogFactory
{
    public function create(int $eventId, bool $isDraft): PrivilegesDialog;
}
