<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\CashbookControl;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\UnitId;

interface ICashbookControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, UnitId $unitId) : CashbookControl;
}
