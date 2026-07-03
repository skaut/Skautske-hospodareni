<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Factories;

use App\Model\Common\UnitId;
use App\Presentation\Unit\Accessory\Components\CreateCashbookDialog;

interface ICreateCashbookDialogFactory
{
    public function create(bool $isEditable, UnitId $unitId): CreateCashbookDialog;
}
