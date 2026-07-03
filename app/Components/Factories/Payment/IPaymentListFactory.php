<?php

declare(strict_types=1);

namespace App\Components\Factories\Payment;

use App\Components\Payment\PaymentList;

interface IPaymentListFactory
{
    public function create(int $groupId, bool $isEditable): PaymentList;
}
