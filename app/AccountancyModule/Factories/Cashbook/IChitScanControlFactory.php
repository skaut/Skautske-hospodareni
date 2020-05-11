<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\ChitScanControl;
use Model\Cashbook\Cashbook\CashbookId;

interface IChitScanControlFactory
{
    public function create(CashbookId $cashbookId, int $chitId, bool $isEditable) : ChitScanControl;
}
