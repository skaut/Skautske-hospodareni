<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\PaymentList;

interface IPaymentListFactory
{
    public function create(int $groupId, bool $isEditable) : PaymentList;
}
