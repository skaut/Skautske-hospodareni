<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories;

use App\AccountancyModule\Components\ChitForm;
use Model\Cashbook\Cashbook\CashbookId;

interface IChitFormFactory
{
    public function create (CashbookId $cashbookId, bool $isEditable) : ChitForm;
}
