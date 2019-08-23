<?php

declare(strict_types=1);

namespace App\AccountancyModule\CampModule\Factories;

use App\AccountancyModule\CampModule\Components\ExportDialog;
use Model\DTO\Camp\CampListItem;

interface IExportDialogFactory
{
    /**
     * @param CampListItem[] $camps
     */
    public function create(array $camps) : ExportDialog;
}
