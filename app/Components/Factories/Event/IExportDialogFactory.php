<?php

declare(strict_types=1);

namespace App\Components\Factories\Event;

use App\Components\Event\ExportDialog;

use App\Model\DTO\Event\EventListItem;

interface IExportDialogFactory
{
    /** @param EventListItem[] $events */
    public function create(array $events): ExportDialog;
}
