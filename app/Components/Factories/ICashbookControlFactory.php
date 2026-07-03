<?php

declare(strict_types=1);

namespace App\Components\Factories;

use App\Components\CashbookControl;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Common\UnitId;

interface ICashbookControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, UnitId $unitId): CashbookControl;
}
