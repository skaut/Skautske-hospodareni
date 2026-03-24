<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Factories;

use App\Model\Common\UnitId;
use App\Presentation\Unit\Accessory\Components\ActivateCashbookDialog;

interface IActivateCashbookDialogFactory
{
    public function create(bool $isEditable, UnitId $unitId): ActivateCashbookDialog;
}
