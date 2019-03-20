<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\PrefixControl;
use Model\Cashbook\Cashbook\CashbookId;

interface IPrefixControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable) : PrefixControl;
}
