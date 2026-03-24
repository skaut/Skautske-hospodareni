<?php

declare(strict_types=1);

namespace App\Presentation\Unit\Accessory\Factories;

use App\Model\Cashbook\Cashbook\CashbookId;
use App\Presentation\Unit\Accessory\Components\ChitListControl;

interface IChitListControlFactory
{
    public function create(CashbookId $cashbookId, bool $onlyUnlocked): ChitListControl;
}
