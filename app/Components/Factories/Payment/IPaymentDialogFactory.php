<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\PaymentDialog;

interface IPaymentDialogFactory
{
    public function create(int $groupId): PaymentDialog;
}
