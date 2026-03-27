<?php

declare(strict_types=1);

namespace App\Components\Factories\Camps;

use App\Components\Camps\ExportDialog;

use App\Model\DTO\Camp\CampListItem;

interface IExportDialogFactory
{
    /** @param CampListItem[] $camps */
    public function create(array $camps): ExportDialog;
}
