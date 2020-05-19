<?php

declare(strict_types=1);

namespace App\AccountancyModule\Factories\Cashbook;

use App\AccountancyModule\Components\Cashbook\PrefixControl;
use Model\Cashbook\Cashbook\CashbookId;
use Model\Cashbook\Cashbook\PaymentMethod;

interface IPrefixControlFactory
{
    public function create(CashbookId $cashbookId, PaymentMethod $paymentMethod, bool $isEditable) : PrefixControl;
}
