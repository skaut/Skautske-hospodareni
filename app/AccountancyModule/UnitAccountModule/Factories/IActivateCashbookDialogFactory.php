<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Factories;

use App\AccountancyModule\UnitAccountModule\Components\ActivateCashbookDialog;
use Model\Common\UnitId;

interface IActivateCashbookDialogFactory
{
    public function create(bool $isEditable, UnitId $unitId) : ActivateCashbookDialog;
}
