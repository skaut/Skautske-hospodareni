<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\ChitListControl;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;

interface IChitListControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, PaymentMethod $paymentMethod) : ChitListControl;
}
