<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\CashbookControl;

interface ICashbookControlFactory
{

    public function create(int $cashbookId, bool $isEditable): CashbookControl;

}
