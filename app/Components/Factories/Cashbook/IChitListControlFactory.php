<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\Cashbook\ChitListControl;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;

interface IChitListControlFactory
{
    public function create(CashbookId $cashbookId, bool $isEditable, PaymentMethod $paymentMethod): ChitListControl;
}
