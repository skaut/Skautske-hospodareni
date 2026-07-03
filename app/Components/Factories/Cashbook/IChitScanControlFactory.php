<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\Cashbook\ChitScanControl;
use App\Model\Cashbook\Cashbook\CashbookId;

interface IChitScanControlFactory
{
    public function create(CashbookId $cashbookId, int $chitId, bool $isEditable): ChitScanControl;
}
