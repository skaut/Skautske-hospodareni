<?php

declare(strict_types=1);

namespace App\AccountancyModule\EventModule\Factories;

use App\AccountancyModule\EventModule\Components\ExportDialog;
use Model\DTO\Event\EventListItem;

interface IExportDialogFactory
{
    /**
     * @param EventListItem[] $events
     */
    public function create(array $events) : ExportDialog;
}
