<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\ChitForm;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Common\UnitId;

interface IChitFormFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, UnitId $unitId) : ChitForm;
}
