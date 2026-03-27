<?php

declare(strict_types=1);

namespace App\Components\Factories\Cashbook;

use App\Components\Cashbook\PrefixControl;
use App\Model\Cashbook\Cashbook\CashbookId;
use App\Model\Cashbook\Cashbook\PaymentMethod;

interface IPrefixControlFactory
{
    public function create(CashbookId $cashbookId, PaymentMethod $paymentMethod, bool $isEditable): PrefixControl;
}
