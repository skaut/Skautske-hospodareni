<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\CashbookControl;
use Model\Cashbook\Cashbook\CashbookId;

interface ICashbookControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable) : CashbookControl;
}
