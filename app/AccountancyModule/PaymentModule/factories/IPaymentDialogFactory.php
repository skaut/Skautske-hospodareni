<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\PaymentDialog;

interface IPaymentDialogFactory
{
    public function create(int $groupId) : PaymentDialog;
}
