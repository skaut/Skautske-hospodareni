<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

use App\AccountancyModule\PaymentModule\Components\EmailButton;
use Model\DTO\Payment\Group;
use Model\DTO\Payment\Payment;

interface IEmailButtonFactory
{
    /** @param Payment[] $payments */
    public function create(bool $isEditable, array $payments, ?Group $group): EmailButton;
}
