<?php

namespace Model\Payment\BankAccount;

interface IAccountNumberValidator
{

    public function validate(?string $prefix, string $number, string $bankCode): bool;

}
