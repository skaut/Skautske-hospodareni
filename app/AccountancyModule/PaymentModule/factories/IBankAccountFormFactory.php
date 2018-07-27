<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule\Factories;

interface IBankAccountFormFactory
{
    public function create(?int $id) : BankAccountForm;
}
