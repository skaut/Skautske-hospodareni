<?php

declare(strict_types=1);

namespace App\AccountancyModule\UnitAccountModule\Factories;

use App\AccountancyModule\UnitAccountModule\Components\ChitListControl;
use Model\Cashbook\Cashbook\CashbookId;

interface IChitListControlFactory
{

    public function create(CashbookId $cashbookId, bool $onlyUnlocked): ChitListControl;

}
