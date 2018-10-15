<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Factories;

use App\AccountancyModule\UnitAccountModule\Components\CreateCashbookDialog;
use Model\Common\UnitId;

interface ICreateCashbookDialogFactory
{
    public function create(bool $isEditable, UnitId $unitId) : CreateCashbookDialog;
}
