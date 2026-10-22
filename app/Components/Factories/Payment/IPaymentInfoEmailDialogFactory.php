<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\PaymentInfoEmailDialog;

interface IPaymentInfoEmailDialogFactory
{
    public function create(int $groupId): PaymentInfoEmailDialog;
}
