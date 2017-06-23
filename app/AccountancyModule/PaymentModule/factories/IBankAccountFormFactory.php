<?php

namespace App\AccountancyModule\PaymentModule\Factories;

interface IBankAccountFormFactory
{

    public function create(?int $id): BankAccountForm;

}
