<?php

declare(strict_types=1);

namespace App\Components\Event;

use App\Model\DTO\Event\EventListItem;

interface IExportDialogFactory
{
    /** @param EventListItem[] $events */
    public function create(array $events): ExportDialog;
}
